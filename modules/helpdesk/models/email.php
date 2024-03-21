<?php
/**
 * @filesource modules/helpdesk/models/email.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Email;

use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ส่งอีเมลและ LINE แจ้งการทำรายการ
     *
     * @param int $id
     * @param int $status_id
     * @param int $private
     *
     * @return string
     */
    public static function send($id, $status_id, $private)
    {
        // ตรวจสอบรายการที่ต้องการ
        $order = \Kotchasan\Model::createQuery()
            ->from('helpdesk R')
            ->join('helpdesk_status A', 'LEFT', array(array('A.helpdesk_id', 'R.id'), array('A.agent_id', '>', 0)))
            ->join('helpdesk_status S', 'LEFT', array(array('S.helpdesk_id', 'R.id'), array('S.id', $status_id)))
            ->join('category C', 'LEFT', array(array('C.category_id', 'R.status'), array('C.type', 'ticketstatus')))
            ->where(array('R.id', $id))
            ->first(
                'R.id',
                'R.ticket_no',
                'R.subject',
                'R.detail',
                'S.comment',
                'R.create_date',
                'R.customer_id',
                Sql::GROUP_CONCAT('A.agent_id', 'agents', ',', true),
                'R.status',
                'C.topic status_text'
            );
        if ($order) {
            $lines = [];
            $emails = [];
            $name = '';
            $mailto = '';
            $line_uid = '';
            // รายชื่อผู้รับ
            if (self::$cfg->demo_mode) {
                // โหมดตัวอย่าง ส่งหาแอดมินเท่านั้น
                $where = array(
                    array('id', [1, $order->customer_id])
                );
            } elseif ($order->status == self::$cfg->helpdesk_first_status) {
                // เปิด Ticket
                $where = array(
                    // ผู้ทำรายการ
                    array('id', $order->customer_id),
                    // แอดมิน
                    array('status', 1),
                    // หัวหน้า Agent
                    array('permission', 'LIKE', '%,can_manage_helpdesk,%'),
                    // Agent
                    array('permission', 'LIKE', '%,helpdesk_agent,%')
                );
            } else {
                // ตอบ Ticket
                $agents = explode(',', $order->agents);
                $agents[] = $order->customer_id;
                $where = array(
                    // ผู้ทำรายการ และ agent ที่รับผิดชอบ
                    array('id', $agents),
                    // แอดมิน
                    array('status', 1),
                    // หัวหน้า Agent
                    array('permission', 'LIKE', '%,can_manage_helpdesk,%')
                );
            }
            // ตรวจสอบรายชื่อผู้รับ
            $query = \Kotchasan\Model::createQuery()
                ->select('id', 'username', 'name', 'line_uid')
                ->from('user')
                ->where(array('active', 1))
                ->andWhere($where, 'OR')
                ->cacheOn();
            foreach ($query->execute() as $item) {
                if ($item->id == $order->customer_id) {
                    // ผู้ทำรายการ
                    $name = $item->name;
                    if ($private == 0) {
                        // ส่งหาผู้ทำรายการ ถ้าไม่ใช่ Private
                        $mailto = $item->username;
                        $line_uid = $item->line_uid;
                    }
                } else {
                    // เจ้าหน้าที่
                    $emails[] = $item->name.'<'.$item->username.'>';
                    if ($item->line_uid != '') {
                        $lines[] = $item->line_uid;
                    }
                }
            }
            // ข้อความ
            $msg = array(
                '{LNG_Helpdesk} : '.$order->ticket_no,
                '{LNG_Customer} : '.$name,
                '{LNG_Created} : '.Date::format($order->create_date),
                '{LNG_Subject} : '.$order->subject,
                '{LNG_Detail} : '.nl2br($order->status == self::$cfg->helpdesk_first_status ? $order->detail : $order->comment),
                '{LNG_Status} : '.$order->status_text
            );
            // ข้อความของ user
            $msg = Language::trans(implode("\n", $msg));
            // ข้อความของแอดมิน
            $admin_msg = $msg."\nURL : ".WEB_URL.'index.php?module=helpdesk-detail&amp;id='.$order->id;
            $line_msg = $admin_msg.'&amp;openExternalBrowser=1';
            // ส่งข้อความ
            $ret = [];
            // LINE Notify
            if (!empty(self::$cfg->line_api_key)) {
                $err = \Gcms\Line::send($line_msg, self::$cfg->line_api_key);
                if ($err != '') {
                    $ret[] = $err;
                }
            }
            if (!empty(self::$cfg->line_channel_access_token)) {
                // LINE ส่วนตัว
                if (!empty($lines)) {
                    $err = \Gcms\Line::sendTo($lines, $line_msg);
                    if ($err != '') {
                        $ret[] = $err;
                    }
                }
                if (!empty($line_uid)) {
                    $err = \Gcms\Line::sendTo($line_uid, $msg);
                    if ($err != '') {
                        $ret[] = $err;
                    }
                }
            }
            if (self::$cfg->noreply_email != '') {
                // หัวข้ออีเมล
                $subject = '['.self::$cfg->web_title.'] '.Language::get('Helpdesk').' '.$order->status_text;
                if (Validator::email($mailto)) {
                    // ส่งอีเมลไปยังผู้ทำรายการ ถ้าไม่ใช่ private
                    $err = \Kotchasan\Email::send($name.'<'.$mailto.'>', self::$cfg->noreply_email, $subject, nl2br($msg));
                    if ($err->error()) {
                        // คืนค่า error
                        $ret[] = strip_tags($err->getErrorMessage());
                    }
                }
                // รายละเอียดในอีเมล (แอดมิน)
                $admin_msg = nl2br($admin_msg);
                foreach ($emails as $item) {
                    // ส่งอีเมล
                    $err = \Kotchasan\Email::send($item, self::$cfg->noreply_email, $subject, $admin_msg);
                    if ($err->error()) {
                        // คืนค่า error
                        $ret[] = strip_tags($err->getErrorMessage());
                    }
                }
            }
            // คืนค่า
            return empty($ret) ? Language::get('Your message was sent successfully') : implode("\n", array_unique($ret));
        }
        // not found
        return Language::get('Sorry, Item not found It&#39;s may be deleted');
    }
}
