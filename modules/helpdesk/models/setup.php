<?php
/**
 * @filesource modules/helpdesk/models/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Setup;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = [];
        if (!empty($params['priority'])) {
            $where[] = array('R.priority', $params['priority']);
        }
        if ($params['status'] > 0) {
            $where[] = array('R.status', $params['status']);
        }
        if (!empty($params['category'])) {
            $where[] = array('R.category', $params['category']);
        }
        if (!empty($params['from'])) {
            $where[] = array(Sql::DATE('R.create_date'), '>=', $params['from']);
        }
        if (!empty($params['to'])) {
            $where[] = array(Sql::DATE('R.create_date'), '<=', $params['to']);
        }
        $query = static::createQuery()
            ->select(
                'R.id',
                'R.customer_id',
                'C.name customer',
                'R.subject',
                'R.ticket_no',
                'R.priority',
                'R.category',
                'R.create_date',
                Sql::GROUP_CONCAT('U.name', 'agents', ', ', true),
                Sql::MAX('A.create_date', 'latest'),
                'R.status'
            )
            ->from('helpdesk R')
            ->join('helpdesk_status A', 'LEFT', array(array('A.helpdesk_id', 'R.id'), array('A.agent_id', '>', 0)))
            ->join('user U', 'LEFT', array('U.id', 'A.agent_id'))
            ->join('user C', 'LEFT', array('C.id', 'R.customer_id'))
            ->where($where)
            ->groupBy('R.id');
        if (!empty($params['agent_id'])) {
            $query->join('helpdesk_status S', 'LEFT', array('S.helpdesk_id', 'R.id'));
            if (in_array($params['status'], [0, self::$cfg->helpdesk_first_status])) {
                $query->andWhere(array(
                    array('S.agent_id', $params['agent_id']),
                    array('R.status', self::$cfg->helpdesk_first_status)
                ), 'OR');
            } else {
                $query->andWhere(array('S.agent_id', $params['agent_id']));
            }
        }
        return $query;
    }

    /**
     * รับค่าจาก action (setup.php)
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
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->filter('0-9,'), $match)) {
                    // Database
                    $db = $this->db();
                    // Table
                    $table_helpdesk = $this->getTableName('helpdesk');
                    $table_status = $this->getTableName('helpdesk_status');
                    if ($action === 'delete' && Login::checkPermission($login, 'can_manage_helpdesk')) {
                        // ลบ DB
                        $db->delete($table_helpdesk, array('id', $match[1]), 0);
                        // ลบไฟล์แนบ
                        $search = $db->select($table_status, array('helpdesk_id', $match[1]));
                        foreach ($search as $item) {
                            File::removeDirectory(ROOT_PATH.DATA_FOLDER.'helpdesk/'.$item['id'].'/');
                        }
                        $db->delete($table_status, array('helpdesk_id', $match[1]), 0);
                        // log
                        \Index\Log\Model::add(0, 'helpdesk', 'Delete', '{LNG_Delete} {LNG_Ticket} ID : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'reopen' && Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent'))) {
                        // อ่านข้อมูลรายการที่ต้องการ
                        $index = $db->first($table_helpdesk, (int) $match[1][0]);
                        if ($index) {
                            $db->update($table_helpdesk, $index->id, array('status' => self::$cfg->helpdesk_reopened_status));
                            // reload
                            $ret['location'] = 'reload';
                        }
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
