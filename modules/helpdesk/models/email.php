<?php
/**
 * @filesource modules/helpdesk/models/email.php
 *
 * @copyright 2026 Goragod.com
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
 * แจ้งเตือนความเคลื่อนไหวของ Ticket ทางอีเมล LINE และ Telegram
 *
 * ผู้รับเหมือนระบบเดิมทุกประการ
 * - เปิด Ticket ใหม่ : ผู้แจ้ง, แอดมิน, หัวหน้า Agent และ Agent ทุกคน
 * - ตอบ Ticket      : ผู้แจ้ง, Agent ที่เคยตอบ, แอดมิน และหัวหน้า Agent
 * ข้อความที่ทำเครื่องหมาย Private จะไม่ถูกส่งให้ผู้แจ้ง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ส่งข้อความแจ้งเตือน คืนค่าข้อความผลลัพธ์สำหรับแสดงให้ผู้ใช้
     *
     * @param int $id ID ของ Ticket
     * @param int $status_id ID ของข้อความที่เพิ่งบันทึก
     * @param int $private 1 = ข้อความภายใน ไม่ส่งให้ผู้แจ้ง
     *
     * @return string
     */
    public static function send($id, $status_id, $private = 0)
    {
        $order = \Kotchasan\Model::createQuery()
            ->select(
                'R.id',
                'R.ticket_no',
                'R.subject',
                'R.detail',
                'R.category',
                'R.priority',
                'R.status',
                'R.created_at',
                'R.customer_id',
                'S.comment',
                Sql::GROUP_CONCAT('A.agent_id', 'agents', ',', true)
            )
            ->from('helpdesk R')
            ->join('helpdesk_status A', [['A.helpdesk_id', 'R.id'], ['A.agent_id', '>', 0]], 'LEFT')
            ->join('helpdesk_status S', [['S.helpdesk_id', 'R.id'], ['S.id', (int) $status_id]], 'LEFT')
            ->where([['R.id', (int) $id]])
            ->groupBy('R.id')
            ->first();

        if (!$order) {
            return Language::get('No data available');
        }

        $isNew = (int) $order->status === (int) self::$cfg->helpdesk_first_status;
        $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
        $priorities = \Helpdesk\Category\Model::map('ticketpriority', false);
        $categories = \Helpdesk\Category\Model::map('category', false);

        // รายชื่อผู้รับ
        if (self::$cfg->demo_mode) {
            // โหมดตัวอย่าง ส่งหาแอดมินและผู้แจ้งเท่านั้น
            $where = [['id', [1, (int) $order->customer_id]]];
        } elseif ($isNew) {
            $where = [
                ['id', (int) $order->customer_id],
                ['status', 1],
                ['permission', 'LIKE', '%,can_manage_helpdesk,%'],
                ['permission', 'LIKE', '%,helpdesk_agent,%']
            ];
        } else {
            $agents = array_filter(array_map('intval', explode(',', (string) $order->agents)));
            $agents[] = (int) $order->customer_id;
            $where = [
                ['id', array_values(array_unique($agents))],
                ['status', 1],
                ['permission', 'LIKE', '%,can_manage_helpdesk,%']
            ];
        }

        $customer = '';
        $mailto = '';
        $line_uid = '';
        $telegram_id = '';
        $emails = [];
        $lines = [];
        $telegrams = [];
        if (!empty(self::$cfg->telegram_chat_id)) {
            $telegrams[self::$cfg->telegram_chat_id] = self::$cfg->telegram_chat_id;
        }

        $recipients = \Kotchasan\Model::createQuery()
            ->select('id', 'username', 'name', 'line_uid', 'telegram_id')
            ->from('user')
            ->where([['active', 1]])
            ->where($where, 'OR')
            ->fetchAll();

        foreach ($recipients as $item) {
            if ((int) $item->id === (int) $order->customer_id) {
                $customer = $item->name;
                if (empty($private)) {
                    $mailto = $item->username;
                    $line_uid = $item->line_uid;
                    $telegram_id = $item->telegram_id;
                }
            } else {
                $emails[] = $item->name.'<'.$item->username.'>';
                if (!empty($item->line_uid)) {
                    $lines[] = $item->line_uid;
                }
                if (!empty($item->telegram_id)) {
                    $telegrams[$item->telegram_id] = $item->telegram_id;
                }
            }
        }

        $message = $isNew ? $order->detail : $order->comment;
        $status_text = \Helpdesk\Category\Model::topicOf($statuses, $order->status);
        $status_color = \Helpdesk\Category\Model::colorOf($statuses, $order->status);
        $url = WEB_URL.'helpdesk-detail?id='.$order->id;

        // ข้อความสั้นสำหรับ LINE และ Telegram (ของเดิมเป็น plain text)
        $plain = Language::trans(implode("\n", [
            '{LNG_Helpdesk} : '.$order->ticket_no,
            '{LNG_Customer} : '.$customer,
            '{LNG_Created} : '.Date::format($order->created_at),
            '{LNG_Subject} : '.$order->subject,
            '{LNG_Status} : '.$status_text,
            '{LNG_Detail} : '.$message
        ]));
        $admin_plain = $plain."\nURL : ".$url;
        $user_plain = $admin_plain.'&openExternalBrowser=1';

        $ret = [];

        // Telegram
        if (!empty(self::$cfg->telegram_bot_token)) {
            if (!empty($telegrams)) {
                $err = \Gcms\Telegram::sendTo($telegrams, $admin_plain);
                if ($err != '') {
                    $ret[] = $err;
                }
            }
            if (!empty($telegram_id)) {
                $err = \Gcms\Telegram::sendTo($telegram_id, $user_plain);
                if ($err != '') {
                    $ret[] = $err;
                }
            }
        }

        // LINE
        if (!empty(self::$cfg->line_channel_access_token)) {
            if (!empty($lines)) {
                $err = \Gcms\Line::sendTo($lines, $admin_plain);
                if ($err != '') {
                    $ret[] = $err;
                }
            }
            if (!empty($line_uid)) {
                $err = \Gcms\Line::sendTo($line_uid, $user_plain);
                if ($err != '') {
                    $ret[] = $err;
                }
            }
        }

        // อีเมล
        if (self::$cfg->noreply_email != '') {
            $subject = '['.self::$cfg->web_title.'] '.Language::get('Helpdesk').' '.$order->ticket_no.' : '.$status_text;
            $variables = [
                'WEB_TITLE' => self::$cfg->web_title,
                'WEB_URL' => WEB_URL,
                'HEADLINE' => Language::get($isNew ? 'New ticket' : 'Ticket updated'),
                'TICKET_NO' => $order->ticket_no,
                'CUSTOMER' => $customer,
                'SUBJECT' => $order->subject,
                'CATEGORY' => \Helpdesk\Category\Model::topicOf($categories, $order->category),
                'PRIORITY' => \Helpdesk\Category\Model::topicOf($priorities, $order->priority),
                'CREATED_AT' => Date::format($order->created_at),
                'STATUS' => $status_text,
                'STATUS_COLOR' => $status_color,
                'MESSAGE' => nl2br(htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8')),
                'URL' => $url
            ];

            if (Validator::email($mailto)) {
                $variables['INTRO'] = self::intro('helpdesk_mail_user', 'Your ticket has been updated.');
                $err = \Kotchasan\Email::send($customer.'<'.$mailto.'>', self::$cfg->noreply_email, $subject, self::render($variables));
                if ($err->error()) {
                    $ret[] = strip_tags($err->getErrorMessage());
                }
            }

            if (!empty($emails)) {
                $variables['INTRO'] = self::intro('helpdesk_mail_agent', 'A ticket needs your attention.');
                $body = self::render($variables);
                foreach ($emails as $item) {
                    $err = \Kotchasan\Email::send($item, self::$cfg->noreply_email, $subject, $body);
                    if ($err->error()) {
                        $ret[] = strip_tags($err->getErrorMessage());
                    }
                }
            }
        }

        return empty($ret)
            ? Language::get('Your message was sent successfully')
            : implode("\n", array_unique($ret));
    }

    /**
     * ข้อความเกริ่นนำของอีเมล แก้ไขได้จากหน้าตั้งค่าของโมดูล
     *
     * @param string $key คีย์ใน config
     * @param string $default ข้อความปริยาย
     *
     * @return string
     */
    protected static function intro($key, $default)
    {
        $text = isset(self::$cfg->{$key}) ? trim((string) self::$cfg->{$key}) : '';

        return $text === '' ? Language::get($default) : $text;
    }

    /**
     * แทนค่าตัวแปรลงในเทมเพลตอีเมลของโมดูล
     *
     * @param array $variables
     *
     * @return string
     */
    protected static function render(array $variables)
    {
        $file = ROOT_PATH.'modules/helpdesk/views/email.html';
        $template = is_file($file) ? file_get_contents($file) : '%MESSAGE%';
        foreach ($variables as $key => $value) {
            $template = str_replace('%'.$key.'%', (string) $value, $template);
        }

        return Language::trans($template);
    }
}
