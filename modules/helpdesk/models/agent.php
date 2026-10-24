<?php
/**
 * @filesource modules/helpdesk/models/agent.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Agent;

/**
 * รายชื่อเจ้าหน้าที่ (Agent) ของศูนย์ช่วยเหลือ
 * คือสมาชิกที่เปิดใช้งานอยู่และมีสิทธิ์ helpdesk_agent หรือ can_manage_helpdesk
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายชื่อ Agent ทั้งหมด
     *
     * @return array
     */
    public static function all()
    {
        return static::createQuery()
            ->select('id', 'name')
            ->from('user')
            ->where([['active', 1]])
            ->where([
                ['permission', 'LIKE', '%,helpdesk_agent,%'],
                ['permission', 'LIKE', '%,can_manage_helpdesk,%'],
                ['status', 1]
            ], 'OR')
            ->orderBy('name')
            ->fetchAll(true);
    }

    /**
     * รายชื่อ Agent ในรูปแบบ [id => name]
     *
     * @return array
     */
    public static function map()
    {
        $result = [];
        foreach (self::all() as $item) {
            $result[(int) $item['id']] = $item['name'];
        }

        return $result;
    }

    /**
     * รายชื่อ Agent สำหรับใส่ลงใน select
     * $login ที่ส่งมาจะถูกเติมเข้าไปด้วยเสมอ เผื่อเป็น Agent ที่เพิ่งถูกถอดสิทธิ์
     *
     * @param object|null $login
     *
     * @return array
     */
    public static function toOptions($login = null)
    {
        $agents = self::map();
        if ($login && !isset($agents[(int) $login->id])) {
            $agents[(int) $login->id] = $login->name;
        }
        $result = [];
        foreach ($agents as $id => $name) {
            $result[] = [
                'value' => $id,
                'text' => $name
            ];
        }

        return $result;
    }

    /**
     * ชื่อ Agent ที่ $id จาก map ที่อ่านมาแล้ว
     *
     * @param array $map ผลจาก map()
     * @param int $id
     *
     * @return string
     */
    public static function nameOf($map, $id)
    {
        return isset($map[(int) $id]) ? $map[(int) $id] : '';
    }
}
