<?php

/**
 * @filesource modules/index/controllers/cron.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Cron;

use Kotchasan\Http\Request;

/**
 * cron.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Controller หลักสำหรับ Cron
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
        define('MAIN_INIT', 'cron');
        // session cookie
        $request->initSession();
        // โหลดโมดูลที่ติดตั้งแล้ว
        $modules = \Gcms\Modules::create();
        // โหลดค่าติดตั้งโมดูล
        foreach ($modules->getControllers('Cron') as $className) {
            if (method_exists($className, 'execute')) {
                // โหลดค่าติดตั้งโมดูล
                $className::execute($request);
            }
        }
    }
}
