<?php
/**
 * @filesource modules/helpdesk/modules/statuses.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Statuses;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-statuses
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สถานะการซ่อม
     *
     * @return array
     */
    public static function all()
    {
        return \Kotchasan\Model::createQuery()
            ->select()
            ->from('category')
            ->order('status_id')
            ->toArray()
            ->execute();
    }

    /**
     * อ่านรายการ สถานะการซ่อม สำหรับใส่ลงใน select
     *
     * @return array
     */
    public static function toSelect()
    {
        $result = [];
        foreach (self::all() as $item) {
            $result[$item['status_id']] = $item['topic'];
        }
        return $result;
    }

    /**
     * อ่านข้อมูลสำหรับใส่ลงในตาราง
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array(
            array('type', $params['type'])
        );
        return static::createQuery()
            ->select('category_id id', 'topic', 'color', 'published')
            ->from('category')
            ->where($where);
    }

    /**
     * รับค่าจากตาราง (statuses.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                $type = $request->post('type')->toString();
                // Database
                $db = $this->db();
                // table
                $table = $this->getTableName('category');
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete') {
                        // ลบ
                        $db->delete($table, array(
                            array('type', $type),
                            array('category_id', $match[1])
                        ), 0);
                        // log
                        \Index\Log\Model::add(0, 'helpdesk', 'Delete', ucfirst($type).' IDs : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'edit') {
                        // แก้ไข
                        $search = \Helpdesk\Statuswrite\Model::get($match[1][0], $type);
                        if ($search) {
                            $ret['modal'] = Language::trans(\Helpdesk\Statuswrite\View::create()->render($request, $search));
                        }
                    } elseif ($action == 'published') {
                        // สถานะ
                        $search = $db->first($table, array(
                            array('type', $type),
                            array('category_id', (int) $match[1][0])
                        ));
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $db->update($table, array(
                                array('type', $search->type),
                                array('category_id', $search->category_id)
                            ), array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->category_id;
                            $ret['class'] = 'icon-published'.$published;
                            // log
                            \Index\Log\Model::add(0, 'helpdesk', 'Status', ucfirst($type).' ID : '.$search->category_id, $login['id']);
                        }
                    }
                }
            }
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
