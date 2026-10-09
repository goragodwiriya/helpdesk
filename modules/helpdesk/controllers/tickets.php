<?php
/**
 * @filesource modules/helpdesk/controllers/tickets.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Tickets;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * GET api/helpdesk/tickets
 * รายการ Ticket ของผู้ที่ล็อกอินอยู่ (แทน module=helpdesk-history ของระบบเดิม)
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
    protected $allowedSortColumns = ['id', 'ticket_no', 'subject', 'status', 'priority', 'category', 'created_at', 'updated_at', 'due_date'];

    /**
     * ทุกคนที่ล็อกอินเปิด Ticket ได้ จึงดูรายการของตัวเองได้เสมอ
     *
     * @param Request $request
     * @param object $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        return $login ? true : $this->errorResponse('Forbidden', 403);
    }

    /**
     * เงื่อนไขเพิ่มเติมจากแถบตัวกรอง
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            // ผูกกับผู้ที่ล็อกอินเสมอ ไม่รับค่าจากภายนอก
            'customer_id' => (int) $login->id,
            'status' => $request->get('status')->toInt(),
            'priority' => $request->get('priority')->toInt(),
            'category' => $request->get('category')->toInt(),
            'from' => $request->get('from')->date(),
            'to' => $request->get('to')->date()
        ];
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
        return \Helpdesk\Jobs\Controller::decorate($datas, false);
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
        return [
            'status' => \Helpdesk\Category\Model::toOptions('ticketstatus', false),
            'category' => \Helpdesk\Category\Model::toOptions('category', false),
            'priority' => \Helpdesk\Category\Model::toOptions('ticketpriority', false)
        ];
    }
}
