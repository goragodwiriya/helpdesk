<?php
/**
 * @filesource modules/helpdesk/models/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Receive;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id ID
     * @param string $category
     *
     * @return object|null
     */
    public static function get($id, $category = '')
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'category' => $category
            );
        } else {
            // แก้ไข
            $q = static::createQuery()
                ->select('helpdesk_id', Sql::MIN('id', 'status_id'))
                ->from('helpdesk_status')
                ->where(array('helpdesk_id', $id));
            return static::createQuery()
                ->select('*', array($q, 'status_id'))
                ->from('helpdesk')
                ->where(array('id', $id));
        }
    }

    /**
     * บันทึกค่าจากฟอร์ม (receive.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // รับค่าจากการ POST
                $save = array(
                    'subject' => $request->post('subject')->topic(),
                    'detail' => $request->post('detail')->textarea()
                );
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('id')->toInt());
                if ($index) {
                    // category
                    $category = \Helpdesk\Category\Model::init();
                    foreach ($category->items() as $key => $label) {
                        if ($key !== 'ticketstatus') {
                            $column = str_replace('ticket', '', $key);
                            $save[$column] = $request->post($key)->topic();
                            if (empty($save[$column])) {
                                $ret['ret_'.$key] = 'Please select';
                            }
                        }
                    }
                    if ($save['subject'] == '') {
                        // ไม่ได้กรอก subject
                        $ret['ret_subject'] = 'Please fill in';
                    }
                    if ($save['detail'] == '') {
                        // ไม่ได้กรอก detail
                        $ret['ret_detail'] = 'Please fill in';
                    }
                    if (empty($ret)) {
                        // Database
                        $db = $this->db();
                        // ตาราง
                        $helpdesk_table = $this->getTableName('helpdesk');
                        $status_table = $this->getTableName('helpdesk_status');
                        if ($index->id == 0) {
                            $status_id = $db->getNextId($status_table);
                        } else {
                            $status_id = $index->status_id;
                        }
                        // อัปโหลดไฟล์
                        \Download\Upload\Model::execute($ret, $request, $status_id, 'helpdesk', self::$cfg->helpdesk_img_typies, 0, self::$cfg->helpdesk_w);
                    }
                    if (empty($ret)) {
                        if ($index->id == 0) {
                            // ใหม่
                            $save['status'] = self::$cfg->helpdesk_first_status;
                            $save['customer_id'] = $login['id'];
                            $save['create_date'] = date('Y-m-d H:i:s');
                            $save['ticket_no'] = \Index\Number\Model::get(0, 'helpdesk_no', $helpdesk_table, 'ticket_no', self::$cfg->helpdesk_prefix);
                            // บันทึก Status รายการแรก
                            $log = array(
                                'id' => $status_id,
                                'helpdesk_id' => $db->insert($helpdesk_table, $save),
                                'member_id' => $save['customer_id'],
                                'comment' => '',
                                'create_date' => $save['create_date'],
                                'agent_id' => 0,
                                'private' => 0
                            );
                            $db->insert($status_table, $log);
                            // ใหม่ ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                            $ret['alert'] = \Helpdesk\Email\Model::send($log['helpdesk_id'], $status_id, 0);
                        } else {
                            // แก้ไขรายการแจ้งซ่อม
                            $db->update($helpdesk_table, $index->id, $save);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                        }
                        // redirect
                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'helpdesk-history', 'id' => null));
                        // clear
                        $request->removeToken();
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
