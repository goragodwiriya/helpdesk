<?php
/**
 * @filesource modules/helpdesk/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Dashboard;

use Kotchasan\Database\Sql;

/**
 * ตัวเลขสรุปสำหรับหน้าแรก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * จำนวน Ticket ของผู้แจ้งรายนี้ แยกตามสถานะ [status => count]
     *
     * @param int $member_id
     *
     * @return array
     */
    public static function myCounts($member_id)
    {
        return self::countByStatus([['customer_id', (int) $member_id]]);
    }

    /**
     * จำนวน Ticket ทั้งระบบ แยกตามสถานะ (สำหรับผู้ดูแล)
     *
     * @return array
     */
    public static function allCounts()
    {
        return self::countByStatus([]);
    }

    /**
     * จำนวน Ticket ของ Agent รายนี้ แยกตามสถานะ
     * นับงานที่เป็นเจ้าของ งานที่เคยตอบ และงานใหม่ที่ยังไม่มีใครรับ
     *
     * @param int $agent_id
     *
     * @return array
     */
    public static function agentCounts($agent_id)
    {
        $agent_id = (int) $agent_id;
        $statusTable = \Kotchasan\DB::create()->getTableName('helpdesk_status');

        return self::countByStatus([
            [
                ['agent_id', $agent_id],
                ['status', (int) self::$cfg->helpdesk_first_status],
                Sql::create('EXISTS(SELECT 1 FROM `'.$statusTable.'` S WHERE S.`helpdesk_id` = `helpdesk`.`id` AND S.`agent_id` = '.$agent_id.')')
            ]
        ], 'OR');
    }

    /**
     * จำนวน Ticket แยกตามสถานะ
     *
     * @param array $where
     * @param string $condition
     *
     * @return array
     */
    protected static function countByStatus($where, $condition = 'AND')
    {
        $query = static::createQuery()
            ->select('status', Sql::COUNT('id', 'count'))
            ->from('helpdesk');
        foreach ($where as $item) {
            $query->where($item, $condition);
        }
        $result = [];
        foreach ($query->groupBy('status')->fetchAll(true) as $item) {
            $result[(int) $item['status']] = (int) $item['count'];
        }

        return $result;
    }

    /**
     * จำนวน Ticket ที่เลยกำหนดส่งและยังไม่ปิดงาน
     *
     * @param int|null $agent_id จำกัดเฉพาะงานของ Agent รายนี้ (null = ทั้งระบบ)
     *
     * @return int
     */
    public static function overdue($agent_id = null)
    {
        $where = [
            [Sql::DATE('due_date'), '<', date('Y-m-d')],
            ['status', '!=', (int) self::$cfg->helpdesk_closed_status]
        ];
        if ($agent_id !== null) {
            $where[] = ['agent_id', (int) $agent_id];
        }
        $row = static::createQuery()
            ->select(Sql::COUNT('id', 'count'))
            ->from('helpdesk')
            ->where($where)
            ->first();

        return empty($row->count) ? 0 : (int) $row->count;
    }

    /**
     * จำนวน Ticket ที่เปิดในแต่ละเดือน ย้อนหลัง $months เดือน
     *
     * คืนค่าเป็น "array ของ series" ตามที่ Now/js/GraphRenderer::setData() ต้องการ
     * [['name' => ชื่อชุดข้อมูล, 'data' => [['label' => .., 'value' => ..], ..]], ..]
     * รูปแบบ {labels, datasets} แบบ Chart.js ใช้กับ GraphComponent ไม่ได้
     *
     * @param int $months
     *
     * @return array
     */
    public static function monthly($months = 12)
    {
        $months = max(1, min(24, (int) $months));
        $begin = date('Y-m-01', strtotime('-'.($months - 1).' month'));
        $rows = static::createQuery()
            ->select(Sql::DATE_FORMAT('created_at', '%Y-%m', 'ym'), Sql::COUNT('id', 'count'))
            ->from('helpdesk')
            ->where([[Sql::DATE('created_at'), '>=', $begin]])
            ->groupBy('ym')
            ->fetchAll(true);

        $counts = [];
        foreach ($rows as $item) {
            $counts[$item['ym']] = (int) $item['count'];
        }
        $points = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $time = strtotime('-'.$i.' month');
            $key = date('Y-m', $time);
            $points[] = [
                'label' => \Kotchasan\Language::get('MONTH_SHORT', null, (int) date('n', $time)).' '.date('y', $time),
                'value' => isset($counts[$key]) ? $counts[$key] : 0
            ];
        }

        return [
            [
                'name' => \Kotchasan\Language::get('Tickets'),
                'data' => $points
            ]
        ];
    }
}
