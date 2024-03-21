<?php
/**
 * @filesource modules/helpdesk/models/reply.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Reply;

use Gcms\Login;
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
     * อ่านข้อมูล Ticket ที่เลือก
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $helpdesk_id ID
     *
     * @return object|null
     */
    public static function get($helpdesk_id)
    {
        return static::createQuery()
            ->from('helpdesk')
            ->where(array('id', $helpdesk_id))
            ->first();
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
                    'comment' => $request->post('comment')->textarea(),
                    'helpdesk_id' => $request->post('helpdesk_id')->toInt(),
                    'create_date' => date('Y-m-d H:i:s')
                );
                // ตรวจสอบรายการที่เลือก
                $index = self::get($save['helpdesk_id']);
                if ($index) {
                    if ($save['comment'] == '') {
                        // ไม่ได้กรอก comment
                        $ret['ret_comment'] = 'Please fill in';
                    }
                    if (empty($ret)) {
                        // Database
                        $db = $this->db();
                        // ตาราง
                        $status_table = $this->getTableName('helpdesk_status');
                        $helpdesk_table = $this->getTableName('helpdesk');
                        // ID
                        $save['id'] = $db->getNextId($status_table);
                        // อัปโหลดไฟล์
                        \Download\Upload\Model::execute($ret, $request, $save['id'], 'helpdesk', self::$cfg->helpdesk_img_typies, 0, self::$cfg->helpdesk_w);
                    }
                    if (empty($ret)) {
                        if ($login['id'] == $index->customer_id) {
                            // Customer
                            $save['member_id'] = $login['id'];
                            $save['agent_id'] = 0;
                            $save['private'] = 0;
                        } else {
                            // Agent
                            $save['member_id'] = 0;
                            $save['agent_id'] = $login['id'];
                            $save['private'] = $request->post('private')->toInt();
                        }
                        $db->insert($status_table, $save);
                        $status = $request->post('status')->toInt();
                        if ($status != $index->status) {
                            $db->update($helpdesk_table, $index->id, array('status' => $status));
                        }
                        // ใหม่ ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                        $ret['alert'] = \Helpdesk\Email\Model::send($index->id, $save['id'], $save['private']);
                        // reload
                        $ret['location'] = 'reload';
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
