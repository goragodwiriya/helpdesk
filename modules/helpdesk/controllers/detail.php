<?php
/**
 * @filesource modules/helpdesk/controllers/detail.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Detail;

use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * GET  api/helpdesk/detail/get         รายละเอียด Ticket พร้อมประวัติการตอบ
 * POST api/helpdesk/detail/reply       ตอบกลับ Ticket
 * POST api/helpdesk/detail/action      ลบข้อความตอบกลับ
 * POST api/helpdesk/detail/removefile  ลบไฟล์แนบของข้อความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * รายละเอียด Ticket
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

            $index = Model::get($request->get('id', 0)->toInt());
            if (!$index) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $isOwner = (int) $index->customer_id === (int) $login->id;
            $isAgent = ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent']);
            if (!$isOwner && !$isAgent) {
                return $this->errorResponse('Permission required', 403);
            }

            $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
            $priorities = \Helpdesk\Category\Model::map('ticketpriority', false);
            $categories = \Helpdesk\Category\Model::map('category', false);
            $closed = (int) self::$cfg->helpdesk_closed_status;
            $isClosed = (int) $index->status === $closed;
            $canManage = ApiController::hasPermission($login, 'can_manage_helpdesk');

            // ตัวเลือกสถานะ ผู้แจ้งเปลี่ยนได้เฉพาะสถานะปัจจุบันกับสถานะปิดงาน เหมือนระบบเดิม
            if ($isAgent) {
                $statusOptions = \Helpdesk\Category\Model::toOptions('ticketstatus');
            } else {
                $statusOptions = [];
                foreach (\Helpdesk\Category\Model::toOptions('ticketstatus') as $item) {
                    if ($item['value'] === (int) $index->status || $item['value'] === $closed) {
                        $statusOptions[] = $item;
                    }
                }
            }

            $replies = [];
            foreach (Model::replies($index, $isAgent) as $item) {
                $replies[] = [
                    'id' => (int) $item->id,
                    'helpdesk_id' => (int) $item->helpdesk_id,
                    'name' => (string) $item->name,
                    'comment' => \Helpdesk\Text\Model::highlighter($item->comment),
                    'created_at' => $item->created_at,
                    'created_text' => Date::format($item->created_at, 'd M Y H:i'),
                    'time_ago' => \Helpdesk\Text\Model::timeAgo($item->created_at),
                    'is_agent' => (int) $item->agent_id > 0 ? 1 : 0,
                    'is_private' => (int) $item->private,
                    'avatar' => ApiController::getAvatarUrl((int) $item->agent_id > 0 ? $item->agent_id : $item->member_id),
                    'initials' => preg_match('/^.{1,2}/u', (string) $item->name, $m) ? $m[0] : '?',
                    'files' => \Helpdesk\Receive\Controller::attachments($item->id),
                    // ลบได้เฉพาะเจ้าของข้อความ หรือผู้ดูแล และต้องยังไม่ปิดงาน
                    'can_delete' => !$isClosed && ($canManage || (int) $item->agent_id === (int) $login->id || (int) $item->member_id === (int) $login->id) ? 1 : 0
                ];
            }

            $data = [
                'id' => (int) $index->id,
                'ticket_no' => (string) $index->ticket_no,
                'subject' => (string) $index->subject,
                'detail' => \Helpdesk\Text\Model::highlighter($index->detail),
                'customer' => (string) $index->customer,
                'customer_id' => (int) $index->customer_id,
                'customer_phone' => (string) $index->customer_phone,
                'customer_status' => (int) $index->customer_status,
                'avatar' => ApiController::getAvatarUrl($index->customer_id),
                'initials' => preg_match('/^.{1,2}/u', (string) $index->customer, $m) ? $m[0] : '?',
                'agent_id' => (int) $index->agent_id,
                'agent' => \Helpdesk\Agent\Model::nameOf(\Helpdesk\Agent\Model::map(), $index->agent_id) ?: Language::get('Unassigned'),
                'category_text' => \Helpdesk\Category\Model::topicOf($categories, $index->category),
                'priority_text' => \Helpdesk\Category\Model::topicOf($priorities, $index->priority),
                'priority_color' => \Helpdesk\Category\Model::colorOf($priorities, $index->priority),
                'status' => (int) $index->status,
                'status_text' => \Helpdesk\Category\Model::topicOf($statuses, $index->status),
                'status_color' => \Helpdesk\Category\Model::colorOf($statuses, $index->status),
                'created_at' => $index->created_at,
                'created_text' => Date::format($index->created_at, 'd M Y H:i'),
                'time_ago' => \Helpdesk\Text\Model::timeAgo($index->created_at),
                'due_date' => empty($index->due_date) ? '' : $index->due_date,
                'due_text' => empty($index->due_date) ? '-' : Date::format($index->due_date, 'd M Y'),
                'is_overdue' => !empty($index->due_date) && $index->due_date < date('Y-m-d') && !$isClosed ? 1 : 0,
                'is_closed' => $isClosed ? 1 : 0,
                'is_agent' => $isAgent ? 1 : 0,
                'can_manage' => $canManage ? 1 : 0,
                'can_edit' => $isAgent || (!$isClosed && $isOwner && !Model::hasReply($index->id, $index->status_id)) ? 1 : 0,
                'accept' => '.'.implode(',.', self::$cfg->helpdesk_img_typies),
                'file_types' => implode(', ', self::$cfg->helpdesk_img_typies),
                'files' => \Helpdesk\Receive\Controller::attachments($index->status_id),
                'replies' => $replies,
                'reply_count' => count($replies)
            ];

            return $this->successResponse([
                'data' => $data,
                'options' => [
                    'status' => $statusOptions,
                    'agent_id' => array_merge(
                        [['value' => 0, 'text' => '{LNG_Unassigned}']],
                        \Helpdesk\Agent\Model::toOptions()
                    )
                ]
            ], 'Ticket details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ตอบกลับ Ticket พร้อมเปลี่ยนสถานะและมอบหมายงาน
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function reply(Request $request)
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

            $index = Model::get($request->post('helpdesk_id')->toInt());
            if (!$index) {
                return $this->errorResponse('No data available', 404);
            }

            $isOwner = (int) $index->customer_id === (int) $login->id;
            $isAgent = ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent']);
            if (!$isOwner && !$isAgent) {
                return $this->errorResponse('Permission required', 403);
            }
            if ((int) $index->status === (int) self::$cfg->helpdesk_closed_status) {
                return $this->errorResponse('This ticket is closed', 400);
            }

            $comment = $request->post('comment')->textarea();
            if ($comment === '') {
                return $this->formErrorResponse(['comment' => 'Please fill in'], 400);
            }

            $db = \Kotchasan\DB::create();
            $status_id = Model::nextId();
            $uploadErrors = [];
            \Download\Upload\Model::execute($uploadErrors, $request, $status_id, 'helpdesk', self::$cfg->helpdesk_img_typies, 0, self::$cfg->helpdesk_w);
            if (!empty($uploadErrors)) {
                return $this->formErrorResponse($uploadErrors, 400);
            }

            $now = date('Y-m-d H:i:s');
            $save = [
                'id' => $status_id,
                'helpdesk_id' => (int) $index->id,
                'comment' => $comment,
                'created_at' => $now
            ];
            // ผู้แจ้งตอบเองบันทึกเป็น member_id เจ้าหน้าที่บันทึกเป็น agent_id เหมือนระบบเดิม
            if ($isOwner && !$isAgent) {
                $save['member_id'] = (int) $login->id;
                $save['agent_id'] = 0;
                $save['private'] = 0;
            } else {
                $save['member_id'] = 0;
                $save['agent_id'] = (int) $login->id;
                $save['private'] = $request->post('private')->toInt() ? 1 : 0;
            }
            $db->insert('helpdesk_status', $save);

            $update = ['updated_at' => $now];
            $status = $request->post('status')->toInt();
            if ($status > 0 && $status !== (int) $index->status && self::isValidStatus($status, $index, $isAgent)) {
                $update['status'] = $status;
            }
            // เจ้าหน้าที่ที่ตอบเป็นคนแรกจะกลายเป็นเจ้าของงานโดยอัตโนมัติ
            if (empty($index->agent_id) && !empty($save['agent_id'])) {
                $update['agent_id'] = $save['agent_id'];
            }
            if (ApiController::hasPermission($login, 'can_manage_helpdesk') && $request->post('agent_id')->exists()) {
                $update['agent_id'] = $request->post('agent_id')->toInt();
            }
            if ($isAgent) {
                $due = $request->post('due_date')->date();
                $update['due_date'] = $due === '' ? null : $due;
            }
            $db->update('helpdesk', [['id', (int) $index->id]], $update);

            \Index\Log\Model::add($index->id, 'helpdesk', 'Reply', 'Reply Ticket : '.$index->ticket_no, $login->id);
            $notify = \Helpdesk\Email\Model::send($index->id, $status_id, $save['private']);

            return $this->redirectResponse('reload', $notify);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ลบข้อความตอบกลับที่เลือก
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
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
            if ($request->post('action')->filter('a-z_') !== 'delete') {
                return $this->errorResponse('Invalid action', 400);
            }

            $index = Model::get($request->post('helpdesk_id')->toInt());
            if (!$index) {
                return $this->errorResponse('No data available', 404);
            }
            if ((int) $index->status === (int) self::$cfg->helpdesk_closed_status) {
                return $this->errorResponse('This ticket is closed', 400);
            }

            $status_id = $request->post('id')->toInt();
            $reply = \Kotchasan\DB::create()->first('helpdesk_status', [
                ['id', $status_id],
                ['helpdesk_id', (int) $index->id]
            ]);
            if (!$reply) {
                return $this->errorResponse('No data available', 404);
            }
            // ลบได้เฉพาะข้อความของตัวเอง หรือเป็นผู้ดูแลศูนย์ช่วยเหลือ
            $isMine = (int) $reply->agent_id === (int) $login->id || (int) $reply->member_id === (int) $login->id;
            if (!$isMine && !ApiController::hasPermission($login, 'can_manage_helpdesk')) {
                return $this->errorResponse('Permission required', 403);
            }
            if (!Model::removeReply($index->id, $status_id)) {
                return $this->errorResponse('Delete action failed', 400);
            }
            \Index\Log\Model::add($index->id, 'helpdesk', 'Delete', 'Delete reply ID : '.$status_id, $login->id);

            return $this->redirectResponse('reload', 'Deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ลบไฟล์แนบของข้อความในหน้ารายละเอียด
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

            $index = Model::get($request->post('helpdesk_id')->toInt());
            if (!$index) {
                return $this->errorResponse('No data available', 404);
            }
            $isOwner = (int) $index->customer_id === (int) $login->id;
            if (!$isOwner && !ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // ไฟล์ต้องอยู่ในโฟลเดอร์ของข้อความที่อยู่ใน Ticket นี้เท่านั้น
            $name = basename($request->post('url')->url());
            $status_id = $request->post('status_id')->toInt();
            if ($name === '' || !in_array($status_id, Model::statusIds($index->id), true)) {
                return $this->errorResponse('No data available', 404);
            }
            $file = ROOT_PATH.DATA_FOLDER.'helpdesk/'.$status_id.'/'.$name;
            if (!is_file($file)) {
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
     * ผู้แจ้งเปลี่ยนสถานะได้เฉพาะสถานะเดิมกับสถานะปิดงาน เจ้าหน้าที่เปลี่ยนได้ทุกสถานะ
     *
     * @param int $status
     * @param object $index
     * @param bool $isAgent
     *
     * @return bool
     */
    protected static function isValidStatus($status, $index, $isAgent)
    {
        $allowed = \Helpdesk\Category\Model::map('ticketstatus');
        if (!isset($allowed[$status])) {
            return false;
        }

        return $isAgent || $status === (int) $index->status || $status === (int) self::$cfg->helpdesk_closed_status;
    }
}
