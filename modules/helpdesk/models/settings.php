<?php
/**
 * @filesource modules/helpdesk/models/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Settings;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * บันทึกข้อมูล (settings.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                try {
                    // โหลด config
                    $config = Config::load(ROOT_PATH.'settings/config.php');
                    // รับค่าจากการ POST
                    $config->helpdesk_first_status = $request->post('helpdesk_first_status')->toInt();
                    $config->helpdesk_prefix = $request->post('helpdesk_prefix')->topic();
                    $config->helpdesk_no = $request->post('helpdesk_no')->topic();
                    $config->helpdesk_closed_status = $request->post('helpdesk_closed_status')->toInt();
                    $config->helpdesk_w = max(100, $request->post('helpdesk_w')->toInt());
                    // save config
                    if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                        // log
                        \Index\Log\Model::add(0, 'helpdesk', 'Save', '{LNG_Module settings} {LNG_Helpdesk}', $login['id']);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    } else {
                        // ไม่สามารถบันทึก config ได้
                        $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
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
