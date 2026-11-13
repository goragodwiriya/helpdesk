<?php
/**
 * @filesource modules/helpdesk/models/text.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Text;

/**
 * แปลงข้อความของ Ticket ให้เป็น HTML
 * ยกมาจาก Helpdesk\Tools\View ของระบบเดิม เพื่อให้ข้อความที่บันทึกไว้แล้ว
 * (ซึ่งเป็น BBCode) แสดงผลได้เหมือนเดิมทุกประการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * แปลง BBCode และลิงก์ในข้อความให้เป็น HTML
     * ข้อความถูก escape ก่อนเสมอ ผลลัพธ์จึงนำไปใส่ innerHTML ได้ปลอดภัย
     *
     * @param string $detail
     *
     * @return string
     */
    public static function highlighter($detail)
    {
        $detail = htmlspecialchars((string) $detail, ENT_QUOTES, 'UTF-8');
        $patt = [];
        $replace = [];
        $patt[] = '/\[(i|dfn|b|strong|u|em|ins|del|sub|sup|small|big)\](.*)\[\/\\1\]/is';
        $replace[] = '<\\1>\\2</\\1>';
        $patt[] = '/\[color=([#a-z0-9]+)\]/i';
        $replace[] = '<span style="color:\\1">';
        $patt[] = '/\[\/(color|size)\]/i';
        $replace[] = '</span>';
        $patt[] = '#\[img\]([^\[]+)\[\/img\]#is';
        $replace[] = '<img src="\\1" alt="Image">';
        $patt[] = '/([^["]]|\r|\n|\s|\t|^)((ftp|https?):\/\/([a-z0-9\.\-_]+)\/([^\s<>\"\']{1,})([^\s<>\"\']{20,20}))/i';
        $replace[] = '\\1<a href="\\2" target="_blank" rel="noopener">\\3://\\4/...\\6</a>';
        $patt[] = '/([^["]]|\r|\n|\s|\t|^)((ftp|https?):\/\/([^\s<>\"\']+))/i';
        $replace[] = '\\1<a href="\\2" target="_blank" rel="noopener">\\2</a>';
        $patt[] = '/(<a[^>]+>)(https?:\/\/[^\%<]+)([\%][^\.\&<]+)([^<]{5,})(<\/a>)/i';
        $replace[] = '\\1\\2...\\4\\5';
        $patt[] = '/\[youtube\]([a-z0-9-_]+)\[\/youtube\]/i';
        $replace[] = '<div class="youtube"><iframe src="//www.youtube.com/embed/\\1?wmode=transparent" allowfullscreen></iframe></div>';

        return nl2br(preg_replace($patt, $replace, $detail));
    }

    /**
     * ระยะเวลานับจาก $date จนถึงตอนนี้ เช่น "2 วัน ที่แล้ว"
     *
     * @param string $date
     *
     * @return string
     */
    public static function timeAgo($date)
    {
        $timestamp = strtotime((string) $date);
        if (empty($timestamp)) {
            return '';
        }
        $diff = time() - $timestamp;
        if ($diff > 31104000) {
            return floor($diff / 31104000).' {LNG_year} {LNG_ago}';
        }
        if ($diff > 2592000) {
            return floor($diff / 2592000).' {LNG_month} {LNG_ago}';
        }
        if ($diff > 604800) {
            return floor($diff / 604800).' {LNG_week} {LNG_ago}';
        }
        $days = floor($diff / 86400);
        if ($days > 1) {
            return $days.' {LNG_days} {LNG_ago}';
        }
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $ret = [];
        if ($days > 0) {
            $ret[] = $days.' {LNG_days}';
        }
        if ($hours > 0) {
            $ret[] = $hours.' {LNG_hrs.}';
        }
        if ($minutes > 0) {
            $ret[] = $minutes.' {LNG_minutes}';
        }

        return empty($ret) ? '{LNG_now}' : implode(' ', $ret);
    }
}
