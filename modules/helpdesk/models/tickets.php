<?php
/**
 * @filesource modules/helpdesk/models/tickets.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Tickets;

use Kotchasan\Database\Sql;

/**
 * Query รายการ Ticket ใช้ร่วมกันทั้งหน้า "Ticket ของฉัน" และ "Ticket (เจ้าหน้าที่)"
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query พื้นฐานของรายการ Ticket
     *
     * เงื่อนไขที่รองรับใน $params
     * customer_id  จำกัดเฉพาะ Ticket ของผู้แจ้งรายนี้
     * agent_id     จำกัดเฉพาะ Ticket ที่ Agent รายนี้เป็นเจ้าของ หรือเคยตอบไว้
     * unassigned   1 = เฉพาะงานที่ยังไม่มีผู้รับผิดชอบ
     * status priority category from to search overdue
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [];
        if (!empty($params['customer_id'])) {
            $where[] = ['R.customer_id', (int) $params['customer_id']];
        }
        if (!empty($params['priority'])) {
            $where[] = ['R.priority', $params['priority']];
        }
        if (!empty($params['status'])) {
            $where[] = ['R.status', (int) $params['status']];
        }
        if (!empty($params['category'])) {
            $where[] = ['R.category', $params['category']];
        }
        if (!empty($params['from'])) {
            $where[] = [Sql::DATE('R.created_at'), '>=', $params['from']];
        }
        if (!empty($params['to'])) {
            $where[] = [Sql::DATE('R.created_at'), '<=', $params['to']];
        }
        if (!empty($params['unassigned'])) {
            $where[] = ['R.agent_id', 0];
        }
        if (!empty($params['overdue'])) {
            $where[] = ['R.due_date', '!=', null];
            $where[] = [Sql::DATE('R.due_date'), '<', date('Y-m-d')];
            $where[] = ['R.status', '!=', (int) self::$cfg->helpdesk_closed_status];
        }

        $query = static::createQuery()
            ->select(
                'R.id',
                'R.ticket_no',
                'R.customer_id',
                'C.name customer',
                'R.agent_id',
                'A.name agent',
                'R.subject',
                'R.category',
                'R.priority',
                'R.status',
                'R.due_date',
                'R.created_at',
                'R.updated_at'
            )
            ->from('helpdesk R')
            ->join('user C', [['C.id', 'R.customer_id']], 'LEFT')
            ->join('user A', [['A.id', 'R.agent_id']], 'LEFT')
            ->where($where);

        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['R.ticket_no', 'LIKE', $keyword],
                ['R.subject', 'LIKE', $keyword],
                ['C.name', 'LIKE', $keyword]
            ], 'OR');
        }

        // Agent เห็นงานที่ตัวเองเป็นเจ้าของ หรือเคยตอบไว้ (ของเดิมนับจากการตอบเท่านั้น)
        if (!empty($params['agent_id'])) {
            $agentId = (int) $params['agent_id'];
            $statusTable = \Kotchasan\DB::create()->getTableName('helpdesk_status');
            $query->where([
                ['R.agent_id', $agentId],
                Sql::create('EXISTS(SELECT 1 FROM `'.$statusTable.'` S WHERE S.`helpdesk_id` = R.`id` AND S.`agent_id` = '.$agentId.')')
            ], 'OR');
        }

        return $query;
    }
}
