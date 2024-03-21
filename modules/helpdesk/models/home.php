<?php
/**
 * @filesource modules/helpdesk/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Home;

use Gcms\Login;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านจำนวน Ticket ตาม Status
     *
     * @return object
     */
    public static function get($login)
    {
        $qs = [];
        // รายการที่เปิด Ticket
        $qs[] = static::createQuery()
            ->select('0 type', 'R.status', 'SQL(COUNT(R.`id`) AS `count`)')
            ->from('helpdesk R')
            ->where(array('R.customer_id', $login['id']))
            ->groupBy('type', 'R.status');
        if (Login::checkPermission($login, 'can_manage_helpdesk')) {
            // สามารถบริหารจัดการได้ (ทุกรายการ)
            $qs[] = static::createQuery()
                ->select('1 type', 'R.status', 'SQL(COUNT(R.`id`) AS `count`)')
                ->from('helpdesk R')
                ->groupBy('type', 'R.status');
        } elseif (Login::checkPermission($login, 'helpdesk_agent', false)) {
            // Agent (เฉพาะรายการที่เป็น Agent)
            $qs[] = static::createQuery()
                ->select('1 type', 'R.status', 'SQL(COUNT(R.`id`) AS `count`)')
                ->from('helpdesk R')
                ->where(array('R.status', self::$cfg->helpdesk_first_status));
            $qs[] = static::createQuery()
                ->select('1 type', 'R.status', 'SQL(COUNT(R.`id`) AS `count`)')
                ->from('helpdesk R')
                ->exists('helpdesk_status', array(array('helpdesk_id', 'R.id'), array('agent_id', $login['id'])))
                ->where(array('R.status', '!=', self::$cfg->helpdesk_first_status))
                ->groupBy('type', 'R.status');
        }
        $query = static::createQuery()
            ->unionAll($qs)
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->type][$item->status] = $item->count;
        }
        return $result;
    }
}
