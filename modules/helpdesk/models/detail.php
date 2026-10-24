<?php
/**
 * @filesource modules/helpdesk/models/detail.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Detail;

use Kotchasan\Database\Sql;

/**
 * รายละเอียดของ Ticket และประวัติการตอบกลับ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่าน Ticket ที่เลือก พร้อม status_id ของข้อความแรก
     * (ข้อความแรกคือเนื้อหาที่ผู้แจ้งเปิดเรื่องไว้ ไฟล์แนบของ Ticket ผูกกับ id นี้)
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }
        $first = static::createQuery()
            ->select(Sql::MIN('id', 'status_id'))
            ->from('helpdesk_status')
            ->where([['helpdesk_id', $id]]);

        return static::createQuery()
            ->select('R.*', 'C.name customer', 'C.status customer_status', 'C.phone customer_phone', [$first, 'status_id'])
            ->from('helpdesk R')
            ->join('user C', [['C.id', 'R.customer_id']], 'LEFT')
            ->where([['R.id', $id]])
            ->first();
    }

    /**
     * อ่านข้อความตอบกลับทั้งหมดของ Ticket ยกเว้นข้อความแรก
     *
     * @param object $index Ticket จาก get()
     * @param bool $includePrivate true = เห็นข้อความภายในของเจ้าหน้าที่ด้วย
     *
     * @return array
     */
    public static function replies($index, $includePrivate = false)
    {
        $where = [
            ['S.helpdesk_id', (int) $index->id],
            ['S.id', '!=', (int) $index->status_id]
        ];
        if (!$includePrivate) {
            $where[] = ['S.private', 0];
        }
        // ผู้เขียนข้อความคือ agent_id ถ้ามี ไม่งั้นคือ member_id
        $author = Sql::create('CASE WHEN S.`agent_id` > 0 THEN S.`agent_id` ELSE S.`member_id` END');

        return static::createQuery()
            ->select('S.id', 'S.helpdesk_id', 'S.comment', 'S.created_at', 'S.private', 'S.agent_id', 'S.member_id', 'U.name', 'U.status user_status')
            ->from('helpdesk_status S')
            ->join('user U', [['U.id', $author]], 'LEFT')
            ->where($where)
            ->orderBy('S.created_at')
            ->orderBy('S.id')
            ->fetchAll();
    }

    /**
     * ID ของข้อความทั้งหมดใน Ticket ใช้เป็นชื่อโฟลเดอร์ไฟล์แนบ
     *
     * @param int $helpdesk_id
     *
     * @return array
     */
    public static function statusIds($helpdesk_id)
    {
        $rows = static::createQuery()
            ->select('id')
            ->from('helpdesk_status')
            ->where([['helpdesk_id', (int) $helpdesk_id]])
            ->fetchAll(true);

        return array_map(fn($item) => (int) $item['id'], $rows);
    }

    /**
     * ID ถัดไปของตาราง helpdesk_status จองไว้ใช้เป็นโฟลเดอร์ไฟล์แนบ
     *
     * @return int
     */
    public static function nextId()
    {
        return \Kotchasan\DB::create()->nextId('helpdesk_status');
    }

    /**
     * Ticket นี้มีข้อความตอบกลับแล้วหรือยัง (ไม่นับข้อความแรกที่เป็นเนื้อหาของ Ticket)
     *
     * @param int $helpdesk_id
     * @param int $status_id ID ของข้อความแรก
     *
     * @return bool
     */
    public static function hasReply($helpdesk_id, $status_id)
    {
        $row = static::createQuery()
            ->select(Sql::COUNT('id', 'count'))
            ->from('helpdesk_status')
            ->where([
                ['helpdesk_id', (int) $helpdesk_id],
                ['id', '!=', (int) $status_id]
            ])
            ->first();

        return !empty($row->count);
    }

    /**
     * ID ของข้อความของหลาย Ticket พร้อมกัน คืนค่า [helpdesk_id => [status_id, ...]]
     *
     * @param array $ids
     *
     * @return array
     */
    public static function statusIdsOf(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        $rows = static::createQuery()
            ->select('id', 'helpdesk_id')
            ->from('helpdesk_status')
            ->where([['helpdesk_id', $ids]])
            ->fetchAll(true);

        $result = array_fill_keys($ids, []);
        foreach ($rows as $item) {
            $result[(int) $item['helpdesk_id']][] = (int) $item['id'];
        }

        return $result;
    }

    /**
     * ลบข้อความตอบกลับ ลบได้เฉพาะข้อความที่อยู่ใน Ticket ที่ระบุ
     * และห้ามลบข้อความแรก เพราะเป็นเนื้อหาของ Ticket เอง
     *
     * @param int $helpdesk_id
     * @param int $status_id
     *
     * @return bool
     */
    public static function removeReply($helpdesk_id, $status_id)
    {
        $db = \Kotchasan\DB::create();
        $row = $db->first('helpdesk_status', [
            ['id', (int) $status_id],
            ['helpdesk_id', (int) $helpdesk_id]
        ]);
        if (!$row) {
            return false;
        }
        $first = static::createQuery()
            ->select(Sql::MIN('id', 'status_id'))
            ->from('helpdesk_status')
            ->where([['helpdesk_id', (int) $helpdesk_id]])
            ->first();
        if ($first && (int) $first->status_id === (int) $row->id) {
            return false;
        }
        \Kotchasan\File::removeDirectory(ROOT_PATH.DATA_FOLDER.'helpdesk/'.$row->id.'/');
        $db->delete('helpdesk_status', [['id', (int) $row->id]]);
        self::touch($helpdesk_id);

        return true;
    }

    /**
     * ปรับ updated_at ของ Ticket ให้ตรงกับข้อความล่าสุด
     *
     * @param int $helpdesk_id
     *
     * @return void
     */
    public static function touch($helpdesk_id)
    {
        $latest = static::createQuery()
            ->select(Sql::MAX('created_at', 'latest'))
            ->from('helpdesk_status')
            ->where([['helpdesk_id', (int) $helpdesk_id]])
            ->first();
        \Kotchasan\DB::create()->update('helpdesk', [['id', (int) $helpdesk_id]], [
            'updated_at' => empty($latest->latest) ? date('Y-m-d H:i:s') : $latest->latest
        ]);
    }

    /**
     * ลบ Ticket พร้อมข้อความตอบกลับและไฟล์แนบทั้งหมด
     *
     * @param array $ids
     *
     * @return int จำนวน Ticket ที่ถูกลบ
     */
    public static function remove(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return 0;
        }
        $db = \Kotchasan\DB::create();
        $rows = static::createQuery()
            ->select('id')
            ->from('helpdesk')
            ->where([['id', $ids]])
            ->fetchAll(true);
        if (empty($rows)) {
            return 0;
        }
        $found = array_map(fn($item) => (int) $item['id'], $rows);
        // ลบไฟล์แนบของทุกข้อความใน Ticket
        $statuses = static::createQuery()
            ->select('id')
            ->from('helpdesk_status')
            ->where([['helpdesk_id', $found]])
            ->fetchAll(true);
        foreach ($statuses as $item) {
            \Kotchasan\File::removeDirectory(ROOT_PATH.DATA_FOLDER.'helpdesk/'.$item['id'].'/');
        }
        $db->delete('helpdesk_status', [['helpdesk_id', $found]], 0);
        $db->delete('helpdesk', [['id', $found]], 0);

        return count($found);
    }
}
