<?php
/**
 * @filesource modules/helpdesk/controllers/receive.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Receive;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * GET  api/helpdesk/receive/get         อ่านข้อมูลลงฟอร์มเปิด Ticket
 * POST api/helpdesk/receive/save        บันทึก Ticket
 * POST api/helpdesk/receive/removefile  ลบไฟล์แนบของ Ticket
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * ข้อมูลสำหรับฟอร์ม
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $index = Model::get($request->get('id', 0)->toInt(), $request->get('category')->topic());
            if (!$index) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }
            if ($index->id > 0) {
                $allowed = self::canEdit($index, $login);
                if ($allowed !== true) {
                    return $this->errorResponse($allowed, 403);
                }
            }

            $canManage = ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent']);

            return $this->successResponse([
                'data' => [
                    'id' => (int) $index->id,
                    'category' => (string) $index->category,
                    'priority' => (string) $index->priority,
                    'subject' => (string) $index->subject,
                    'detail' => (string) $index->detail,
                    'due_date' => empty($index->due_date) ? '' : $index->due_date,
                    'can_manage' => $canManage ? 1 : 0,
                    'accept' => '.'.implode(',.', self::$cfg->helpdesk_img_typies),
                    'file_types' => implode(', ', self::$cfg->helpdesk_img_typies),
                    'attachments' => $index->id > 0 ? self::attachments($index->status_id) : []
                ],
                'options' => [
                    'category' => \Helpdesk\Category\Model::toOptions('category'),
                    'priority' => \Helpdesk\Category\Model::toOptions('ticketpriority')
                ]
            ], 'Ticket retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * บันทึก Ticket
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Unable to complete the transaction', 403);
            }

            $index = Model::get($request->post('id')->toInt());
            if (!$index) {
                return $this->errorResponse('No data available', 404);
            }
            if ($index->id > 0) {
                $allowed = self::canEdit($index, $login);
                if ($allowed !== true) {
                    return $this->errorResponse($allowed, 403);
                }
            }

            $save = [
                'category' => $request->post('category')->topic(),
                'priority' => $request->post('priority')->topic(),
                'subject' => $request->post('subject')->topic(),
                'detail' => $request->post('detail')->textarea()
            ];

            $errors = [];
            if ($save['category'] === '') {
                $errors['category'] = 'Please select';
            }
            if ($save['priority'] === '') {
                $errors['priority'] = 'Please select';
            }
            if ($save['subject'] === '') {
                $errors['subject'] = 'Please fill in';
            }
            if ($save['detail'] === '') {
                $errors['detail'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            // เจ้าหน้าที่กำหนดวันครบกำหนดได้เอง ผู้แจ้งทั่วไปใช้ค่าจากการตั้งค่า
            $canManage = ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent']);
            if ($canManage) {
                $due = $request->post('due_date')->date();
                $save['due_date'] = $due === '' ? null : $due;
            } elseif ($index->id === 0) {
                $days = (int) self::$cfg->helpdesk_sla_days;
                $save['due_date'] = $days > 0 ? date('Y-m-d', strtotime('+'.$days.' day')) : null;
            }

            if ($index->id > 0) {
                // แก้ไข Ticket เดิม ไฟล์แนบผูกกับข้อความแรกเสมอ
                $status_id = (int) $index->status_id;
                $uploadErrors = [];
                \Download\Upload\Model::execute($uploadErrors, $request, $status_id, 'helpdesk', self::$cfg->helpdesk_img_typies, 0, self::$cfg->helpdesk_w);
                if (!empty($uploadErrors)) {
                    return $this->formErrorResponse($uploadErrors, 400);
                }
                $save['updated_at'] = date('Y-m-d H:i:s');
                \Kotchasan\DB::create()->update('helpdesk', [['id', (int) $index->id]], $save);
                \Index\Log\Model::add($index->id, 'helpdesk', 'Save', 'Edit Ticket : '.$index->ticket_no, $login->id);

                return $this->redirectResponse('/helpdesk-detail?id='.$index->id, 'Saved successfully', 200, 1000);
            }

            // Ticket ใหม่ จอง ID ของข้อความแรกไว้ก่อน เพื่อใช้เป็นโฟลเดอร์ไฟล์แนบ
            $status_id = Model::nextStatusId();
            $uploadErrors = [];
            \Download\Upload\Model::execute($uploadErrors, $request, $status_id, 'helpdesk', self::$cfg->helpdesk_img_typies, 0, self::$cfg->helpdesk_w);
            if (!empty($uploadErrors)) {
                return $this->formErrorResponse($uploadErrors, 400);
            }

            $save['status'] = (int) self::$cfg->helpdesk_first_status;
            $save['customer_id'] = (int) $login->id;
            $save['agent_id'] = 0;
            $save['created_at'] = date('Y-m-d H:i:s');
            $save['updated_at'] = $save['created_at'];
            $save['ticket_no'] = \Index\Number\Model::get(0, self::$cfg->helpdesk_no, \Kotchasan\DB::create()->getTableName('helpdesk'), 'ticket_no', self::$cfg->helpdesk_prefix);

            list($helpdesk_id) = Model::createTicket($save, $status_id);

            \Index\Log\Model::add($helpdesk_id, 'helpdesk', 'Save', 'New Ticket : '.$save['ticket_no'], $login->id);
            $notify = \Helpdesk\Email\Model::send($helpdesk_id, $status_id, 0);

            return $this->redirectResponse('/helpdesk-detail?id='.$helpdesk_id, $notify, 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ลบไฟล์แนบของ Ticket
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function removefile(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Unable to complete the transaction', 403);
            }
            if ($request->post('action')->filter('a-z') !== 'delete') {
                return $this->errorResponse('Invalid action', 400);
            }

            $index = Model::get($request->post('id')->toInt());
            if (!$index || $index->id === 0) {
                return $this->errorResponse('No data available', 404);
            }
            $allowed = self::canEdit($index, $login);
            if ($allowed !== true) {
                return $this->errorResponse($allowed, 403);
            }

            // ยอมรับเฉพาะไฟล์ที่อยู่ในโฟลเดอร์ของข้อความแรกของ Ticket นี้เท่านั้น
            $name = basename($request->post('url')->url());
            $file = ROOT_PATH.DATA_FOLDER.'helpdesk/'.((int) $index->status_id).'/'.$name;
            if ($name === '' || !is_file($file)) {
                return $this->errorResponse('No data available', 404);
            }
            unlink($file);
            \Index\Log\Model::add($index->id, 'helpdesk', 'Delete', 'Remove attachment : '.$name, $login->id);

            return $this->successResponse(null, 'Deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * รายการไฟล์แนบของข้อความ
     *
     * @param int $status_id
     *
     * @return array
     */
    public static function attachments($status_id)
    {
        $result = [];
        foreach (\Download\Index\Controller::getAttachments((int) $status_id, 'helpdesk', self::$cfg->helpdesk_img_typies) as $file) {
            $result[] = [
                'url' => $file['url'],
                'name' => $file['name'],
                'icon' => $file['icon'],
                'is_image' => $file['is_image']
            ];
        }

        return $result;
    }

    /**
     * ตรวจสิทธิ์การแก้ไข Ticket
     * ผู้แจ้งแก้ไขได้เฉพาะตอนที่ยังไม่มีใครตอบและยังไม่ปิดงาน เจ้าหน้าที่แก้ไขได้เสมอ
     *
     * @param object $index
     * @param object $login
     *
     * @return true|string
     */
    protected static function canEdit($index, $login)
    {
        if (ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
            return true;
        }
        if ((int) $index->customer_id !== (int) $login->id) {
            return 'Permission required';
        }
        if ((int) $index->status === (int) self::$cfg->helpdesk_closed_status) {
            return Language::get('This ticket is closed');
        }
        if (\Helpdesk\Detail\Model::hasReply($index->id, $index->status_id)) {
            return Language::get('This ticket has already been answered');
        }

        return true;
    }
}
