<?php
/**
 * @filesource modules/helpdesk/models/receive.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Receive;

use Kotchasan\Database\Sql;

/**
 * เปิด Ticket ใหม่ และแก้ไข Ticket ที่ยังไม่มีการตอบกลับ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่าน Ticket ที่ต้องการแก้ไข
     * $id = 0 คืนค่าโครงของรายการใหม่
     *
     * @param int $id
     * @param string $category หมวดหมู่ตั้งต้นของรายการใหม่
     *
     * @return object|null
     */
    public static function get($id, $category = '')
    {
        $id = (int) $id;
        if ($id < 1) {
            return (object) [
                'id' => 0,
                'customer_id' => 0,
                'category' => $category,
                'priority' => '',
                'subject' => '',
                'detail' => '',
                'status' => 0,
                'status_id' => 0,
                'due_date' => null
            ];
        }
        $first = static::createQuery()
            ->select(Sql::MIN('id', 'status_id'))
            ->from('helpdesk_status')
            ->where([['helpdesk_id', $id]]);

        return static::createQuery()
            ->select('R.*', [$first, 'status_id'])
            ->from('helpdesk R')
            ->where([['R.id', $id]])
            ->first();
    }

    /**
     * บันทึก Ticket ใหม่ คืนค่า [helpdesk_id, status_id]
     *
     * @param array $save ข้อมูลของ Ticket
     * @param int $status_id ID ของข้อความแรกที่จองไว้ล่วงหน้า (ใช้เป็นโฟลเดอร์ไฟล์แนบ)
     *
     * @return array
     */
    public static function createTicket(array $save, $status_id)
    {
        $db = \Kotchasan\DB::create();
        $helpdesk_id = $db->insert('helpdesk', $save);
        $db->insert('helpdesk_status', [
            'id' => $status_id,
            'helpdesk_id' => $helpdesk_id,
            'comment' => '',
            'agent_id' => 0,
            'member_id' => $save['customer_id'],
            'created_at' => $save['created_at'],
            'private' => 0
        ]);

        return [$helpdesk_id, $status_id];
    }

    /**
     * จอง ID ของข้อความถัดไปในตาราง helpdesk_status
     * ใช้เป็นชื่อโฟลเดอร์ไฟล์แนบก่อนที่จะบันทึกข้อความจริง
     *
     * @return int
     */
    public static function nextStatusId()
    {
        return \Kotchasan\DB::create()->nextId('helpdesk_status');
    }
}
