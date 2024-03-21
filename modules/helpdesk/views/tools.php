<?php
/**
 * @filesource modules/helpdesk/views/tools.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Tools;

/**
 * module=repair-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟังก์ชั่น HTML highlighter
     * ทำ highlight ข้อความส่วนที่เป็นโค้ด
     * จัดการแปลง BBCode
     * แปลงข้อความ http เป็นลิงค์
     * คืนค่าข้อความ
     *
     * @param string $detail  ข้อความ
     *
     * @return string
     */
    public static function highlighter($detail)
    {
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
        $replace[] = '\\1<a href="\\2" target="_blank">\\3://\\4/...\\6</a>';
        $patt[] = '/([^["]]|\r|\n|\s|\t|^)((ftp|https?):\/\/([^\s<>\"\']+))/i';
        $replace[] = '\\1<a href="\\2" target="_blank">\\2</a>';
        $patt[] = '/(<a[^>]+>)(https?:\/\/[^\%<]+)([\%][^\.\&<]+)([^<]{5,})(<\/a>)/i';
        $replace[] = '\\1\\2...\\4\\5';
        $patt[] = '/\[youtube\]([a-z0-9-_]+)\[\/youtube\]/i';
        $replace[] = '<div class="youtube"><iframe src="//www.youtube.com/embed/\\1?wmode=transparent" allowfullscreen></iframe></div>';
        return nl2br(preg_replace($patt, $replace, $detail));
    }

    /**
     * แปลงนาที เป็น เวลา
     *
     * @param string $date
     *
     * @return array
     */
    public static function timeAgo($date)
    {
        $date_timestamp = strtotime($date);
        $now = time();
        $diff_seconds = $now - $date_timestamp;
        if ($diff_seconds > 31104000) {
            $year = floor($diff_seconds / 31104000);
            return $year.' {LNG_year} {LNG_ago}';
        }
        if ($diff_seconds > 2592000) {
            $month = floor($diff_seconds / 2592000);
            return $month.' {LNG_month} {LNG_ago}';
        }
        if ($diff_seconds > 604800) {
            $week = floor($diff_seconds / 604800);
            return $week.' {LNG_week} {LNG_ago}';
        }
        // วัน
        $days = floor($diff_seconds / 86400);
        if ($days > 1) {
            return $days.' {LNG_days} {LNG_ago}';
        }
        // ชั่วโมง
        $hours = floor(($diff_seconds % 86400) / 3600);
        // นาที
        $minutes = floor(($diff_seconds % 3600) / 60);
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
        return implode(' ', $ret);
    }
}
