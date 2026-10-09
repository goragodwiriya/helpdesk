<?php
/**
 * @filesource modules/helpdesk/controllers/jobs.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Jobs;

use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * GET  api/helpdesk/jobs         รายการ Ticket ของเจ้าหน้าที่ (แทน module=helpdesk-setup)
 * POST api/helpdesk/jobs/action  ลบ / เปิดงานใหม่ / รับงาน
 * GET  api/helpdesk/jobs/export  ดาวน์โหลด CSV
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * คอลัมน์ที่เรียงลำดับได้
     *
     * @var array
     */
    protected $allowedSortColumns = ['id', 'ticket_no', 'subject', 'status', 'priority', 'category', 'created_at', 'updated_at', 'due_date', 'agent_id'];

    /**
     * เฉพาะผู้ดูแลศูนย์ช่วยเหลือและเจ้าหน้าที่
     *
     * @param Request $request
     * @param object $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * เงื่อนไขจากแถบตัวกรอง
     * เจ้าหน้าที่ที่ไม่ใช่ผู้ดูแล เห็นเฉพาะงานของตัวเองเท่านั้น
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $params = [
            'status' => $request->get('status')->toInt(),
            'priority' => $request->get('priority')->toInt(),
            'category' => $request->get('category')->toInt(),
            'from' => $request->get('from')->date(),
            'to' => $request->get('to')->date(),
            'overdue' => $request->get('overdue')->toInt()
        ];
        if (ApiController::hasPermission($login, 'can_manage_helpdesk')) {
            $agent_id = $request->get('agent_id')->toInt();
            // -1 = เฉพาะงานที่ยังไม่มีผู้รับผิดชอบ
            if ($agent_id === -1) {
                $params['unassigned'] = 1;
            } elseif ($agent_id > 0) {
                $params['agent_id'] = $agent_id;
            }
        } else {
            $params['agent_id'] = (int) $login->id;
        }

        return $params;
    }

    /**
     * Query ของตาราง
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        return \Helpdesk\Tickets\Model::toDataTable($params);
    }

    /**
     * เติมข้อความและสีของสถานะให้แต่ละแถว
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        return self::decorate($datas, ApiController::hasPermission($login, 'can_manage_helpdesk'));
    }

    /**
     * ตัวเลือกของคอลัมน์ที่กรองได้
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $filters = [
            'status' => \Helpdesk\Category\Model::toOptions('ticketstatus', false),
            'category' => \Helpdesk\Category\Model::toOptions('category', false),
            'priority' => \Helpdesk\Category\Model::toOptions('ticketpriority', false)
        ];
        if (ApiController::hasPermission($login, 'can_manage_helpdesk')) {
            $filters['agent_id'] = array_merge(
                [['value' => -1, 'text' => '{LNG_Unassigned}']],
                \Helpdesk\Agent\Model::toOptions()
            );
        }

        return $filters;
    }

    /**
     * เติมข้อความ สี และสิทธิ์ที่ฝั่งเทมเพลตต้องใช้ ลงในแต่ละแถว
     * ใช้ร่วมกับตาราง Ticket ของฉันด้วย
     *
     * @param array $datas
     * @param bool $canManage
     *
     * @return array
     */
    public static function decorate(array $datas, $canManage)
    {
        $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
        $priorities = \Helpdesk\Category\Model::map('ticketpriority', false);
        $categories = \Helpdesk\Category\Model::map('category', false);
        $today = date('Y-m-d');
        $closed = (int) self::$cfg->helpdesk_closed_status;

        $attachments = self::attachmentCounts(array_map(fn($item) => (int) $item->id, $datas));

        foreach ($datas as $item) {
            $item->status_text = \Helpdesk\Category\Model::topicOf($statuses, $item->status);
            $item->status_color = \Helpdesk\Category\Model::colorOf($statuses, $item->status);
            $item->priority_text = \Helpdesk\Category\Model::topicOf($priorities, $item->priority);
            $item->priority_color = \Helpdesk\Category\Model::colorOf($priorities, $item->priority);
            $item->category_text = \Helpdesk\Category\Model::topicOf($categories, $item->category);
            $item->agent = empty($item->agent) ? Language::get('Unassigned') : $item->agent;
            $item->avatar = ApiController::getAvatarUrl($item->customer_id);
            $item->initials = self::initials($item->customer);
            $item->attachments = $attachments[(int) $item->id] ?? 0;
            $item->is_closed = (int) $item->status === $closed ? 1 : 0;
            $item->is_overdue = !empty($item->due_date) && $item->due_date < $today && !$item->is_closed ? 1 : 0;
            $item->due_text = empty($item->due_date) ? '' : Date::format($item->due_date, 'd M Y');
            $item->created_text = Date::format($item->created_at, 'd M Y H:i');
            $item->updated_text = empty($item->updated_at) ? '' : Date::format($item->updated_at, 'd M Y H:i');
            $item->created_text = Date::format($item->created_at, 'd M Y H:i');
            $item->updated_text = empty($item->updated_at) ? $item->created_text : Date::format($item->updated_at, 'd M Y H:i');
            $item->can_manage = $canManage ? 1 : 0;
        }

        return $datas;
    }

    /**
     * ตัวอักษรย่อของชื่อ ใช้แทนรูปประจำตัวเมื่อไม่มีไฟล์รูป
     *
     * @param string $name
     *
     * @return string
     */
    protected static function initials($name)
    {
        return preg_match('/^.{1,2}/u', (string) $name, $match) ? $match[0] : '?';
    }

    /**
     * จำนวนไฟล์แนบของ Ticket หลายรายการพร้อมกัน คืนค่า [helpdesk_id => จำนวนไฟล์]
     * อ่าน id ของข้อความทั้งหมดในคราวเดียว เพื่อไม่ให้เกิด query ต่อแถว
     *
     * @param array $ids
     *
     * @return array
     */
    protected static function attachmentCounts(array $ids)
    {
        $result = [];
        foreach (\Helpdesk\Detail\Model::statusIdsOf($ids) as $helpdesk_id => $statusIds) {
            $count = 0;
            foreach ($statusIds as $status_id) {
                $files = [];
                \Kotchasan\File::listFiles(ROOT_PATH.DATA_FOLDER.'helpdesk/'.$status_id.'/', $files);
                $count += count($files);
            }
            $result[$helpdesk_id] = $count;
        }

        return $result;
    }

    /**
     * POST api/helpdesk/jobs/action (action=delete)
     * ลบ Ticket ที่เลือก พร้อมข้อความตอบกลับและไฟล์แนบ
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_helpdesk'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $count = \Helpdesk\Detail\Model::remove($ids);
        if (empty($count)) {
            return $this->errorResponse('Delete action failed', 400);
        }
        \Index\Log\Model::add(0, 'helpdesk', 'Delete', 'Delete Ticket ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$count.' item(s) successfully', 200, 0, 'table');
    }

    /**
     * POST api/helpdesk/jobs/action (action=reopen)
     * เปิด Ticket ที่ปิดไปแล้วขึ้นมาใหม่
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleReopenAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $index = \Helpdesk\Detail\Model::get($id);
        if (!$index) {
            return $this->errorResponse('No data available', 404);
        }
        // ผู้แจ้งเปิดงานของตัวเองใหม่ได้ นอกนั้นต้องเป็นเจ้าหน้าที่
        $isOwner = (int) $index->customer_id === (int) $login->id;
        if (!$isOwner && !ApiController::canModify($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
            return $this->errorResponse('Permission required', 403);
        }
        if (!ApiController::isNotDemoMode($login)) {
            return $this->errorResponse('Unable to complete the transaction', 403);
        }
        if ((int) $index->status !== (int) self::$cfg->helpdesk_closed_status) {
            return $this->errorResponse('This ticket is not closed', 400);
        }
        \Kotchasan\DB::create()->update('helpdesk', [['id', (int) $index->id]], [
            'status' => (int) self::$cfg->helpdesk_reopened_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        \Index\Log\Model::add($index->id, 'helpdesk', 'Reopen', 'Reopen Ticket : '.$index->ticket_no, $login->id);

        return $this->redirectResponse('reload', 'Saved successfully');
    }

    /**
     * POST api/helpdesk/jobs/action (action=claim)
     * เจ้าหน้าที่รับงานที่ยังไม่มีผู้รับผิดชอบมาเป็นของตัวเอง
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleClaimAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $index = \Helpdesk\Detail\Model::get($request->post('id')->toInt());
        if (!$index) {
            return $this->errorResponse('No data available', 404);
        }
        if (!empty($index->agent_id) && (int) $index->agent_id !== (int) $login->id
            && !ApiController::hasPermission($login, 'can_manage_helpdesk')) {
            return $this->errorResponse('This ticket already has an assignee', 400);
        }
        \Kotchasan\DB::create()->update('helpdesk', [['id', (int) $index->id]], ['agent_id' => (int) $login->id]);
        \Index\Log\Model::add($index->id, 'helpdesk', 'Assign', 'Assign Ticket : '.$index->ticket_no, $login->id);

        return $this->redirectResponse('reload', 'Saved successfully', 200, 0, 'table');
    }

    /**
     * GET api/helpdesk/jobs/export?type=csv
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
        $priorities = \Helpdesk\Category\Model::map('ticketpriority', false);
        $categories = \Helpdesk\Category\Model::map('category', false);

        $headers = [
            Language::get('Ticket No.'),
            Language::get('Created'),
            Language::get('Customer'),
            Language::get('Subject'),
            Language::get('Category'),
            Language::get('Priority'),
            Language::get('Status'),
            Language::get('Assign to'),
            Language::get('Due Date'),
            Language::get('Updated')
        ];

        $this->exportToCsv($params, $login, $headers, function ($row) use ($statuses, $priorities, $categories) {
            return [
                $row->ticket_no,
                $row->created_at,
                $row->customer,
                $row->subject,
                \Helpdesk\Category\Model::topicOf($categories, $row->category),
                \Helpdesk\Category\Model::topicOf($priorities, $row->priority),
                \Helpdesk\Category\Model::topicOf($statuses, $row->status),
                $row->agent,
                $row->due_date,
                $row->updated_at
            ];
        }, 'helpdesk-'.date('YmdHis'));
    }
}
