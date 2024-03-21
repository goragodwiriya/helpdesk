<?php
/**
 * @filesource modules/helpdesk/models/statuswrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Statuswrite;

use Gcms\Login;
use Kotchasan\Database\Sql;
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
     * อ่านรายการที่เลือก
     * $id = 0 รายการใหม่
     * ตคืนค่า object ไม่พบข้อมูลคืนค่า false
     *
     * @param int $id
     * @param string $type
     *
     * @return mixed
     */
    public static function get($id, $type)
    {
        if ($id > 0) {
            return static::createQuery()
                ->from('category')
                ->where(array(
                    array('type', $type),
                    array('category_id', $id)
                ))
                ->first('*', 'category_id id');
        } else {
            // ใหม่ คืนค่า ID ถัดไป
            $search = static::createQuery()
                ->from('category')
                ->where(array('type', $type))
                ->first(Sql::create('MAX(CAST(`category_id` AS INT)) AS `category_id`'));
            return (object) array(
                'id' => 0,
                'type' => $type,
                'category_id' => empty($search->category_id) ? 1 : (1 + (int) $search->category_id)
            );
        }
    }

    /**
     * บันทึกข้อมูล (statuswrite.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'category_id' => $request->post('status_category_id')->toInt(),
                        'topic' => $request->post('status_topic')->topic(),
                        'color' => $request->post('status_color')->filter('#A-Z0-9'),
                        'type' => $request->post('status_type')->filter('a-z_')
                    );
                    // รายการที่แก้ไข
                    $index = self::get($request->post('status_id')->toInt(), $save['type']);
                    // แอดมิน
                    if ($index) {
                        // category_id
                        if ($save['category_id'] == 0) {
                            $ret['ret_status_category_id'] = 'Please fill in';
                        }
                        // topic
                        if (empty($save['topic'])) {
                            $ret['ret_status_topic'] = 'Please fill in';
                        }
                        // color
                        if (empty($save['color'])) {
                            $ret['ret_status_color'] = 'Please select';
                        }
                        if (empty($ret)) {
                            // Databse
                            $db = $this->db();
                            // Table
                            $table = $this->getTableName('category');
                            // ตรวจสอบ category_id ซ้ำ
                            $search = $db->first($table, array(
                                array('type', $save['type']),
                                array('category_id', $save['category_id'])
                            ));
                            if ($search && ($index->id == 0 || $search->category_id != $index->id)) {
                                $ret['ret_status_category_id'] = Language::replace('This :name already exist', array(':name' => 'ID'));
                            }
                        }
                        if (empty($ret)) {
                            if ($index->id == 0) {
                                // ใหม่
                                $db->insert($table, $save);
                            } else {
                                // แก้ไข
                                $db->update($table, array(
                                    array('type', $index->type),
                                    array('category_id', $index->id)
                                ), $save);
                            }
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['modal'] = 'close';
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
