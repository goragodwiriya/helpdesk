<?php
/**
 * @filesource modules/helpdesk/models/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Detail;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายละเอียดการทำรายการจาก $id
     *
     * @param int $id
     *
     * @return object
     */
    public static function get($id)
    {
        $q = static::createQuery()
            ->select(Sql::MIN('id', 'min_id'))
            ->from('helpdesk_status')
            ->where(array('helpdesk_id', $id));
        return static::createQuery()
            ->from('helpdesk R')
            ->join('user U', 'LEFT', array('U.id', 'R.customer_id'))
            ->where(array('R.id', $id))
            ->first('R.*', 'U.name', 'U.status ustatus', 'U.phone', array($q, 'status_id'));
    }

    /**
     * อ่าน Reply ทั้งหมด
     *
     * @param object $index
     * @param bool $private
     *
     * @return array
     */
    public static function replys($index, $private = true)
    {
        $where = array(
            array('S.helpdesk_id', $index->id),
            array('S.id', '!=', $index->status_id)
        );
        if (!$private) {
            $where[] = array('S.private', 0);
        }
        $q = 'SQL(CASE WHEN S.`agent_id`>0 THEN S.`agent_id` ELSE S.`member_id` END)';
        return static::createQuery()
            ->select('U.name', 'U.status ustatus', 'U.id member_id', 'S.create_date', 'S.comment', 'S.id', 'S.helpdesk_id', 'S.private')
            ->from('helpdesk_status S')
            ->join('user U', 'LEFT', array('U.id', $q))
            ->where($where)
            ->order('S.create_date')
            ->execute();
    }

    /**
     * รับค่าจาก action (detail.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                $id = $request->post('id')->toString();
                // id ที่ส่งมา
                if (preg_match('/^delete_([0-9a-z]+)$/', $id, $match)) {
                    if (isset($_SESSION[$match[1]])) {
                        $file = $_SESSION[$match[1]];
                        if (is_file($file['file'])) {
                            unlink($file['file']);
                        }
                        // คืนค่ารายการที่ลบ
                        $ret['remove'] = 'item_'.$match[1];
                    }
                } elseif (preg_match_all('/,?([0-9]+),?/', $id, $match)) {
                    if ($action === 'delete' && Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent'))) {
                        // ลบรายละเอียดซ่อม
                        $this->db()->delete($this->getTableName('helpdesk_status'), array('id', (int) $match[1][0]));
                        // reload
                        $ret['location'] = 'reload';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
