<?php
/**
 * @filesource modules/helpdesk/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        // เมนู Helpdesk เห็นทุกคน
        $submenus = array(
            array(
                'text' => '{LNG_Create ticket}',
                'url' => 'index.php?module=helpdesk-receive'
            ),
            array(
                'text' => '{LNG_My tickets}',
                'url' => 'index.php?module=helpdesk-history'
            )
        );
        // สามารถบริหารจัดการได้, Agent
        if (Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent'))) {
            $submenus[] = array(
                'text' => '{LNG_Tickets} ({LNG_Agent})',
                'url' => 'index.php?module=helpdesk-setup'
            );
        }
        // เมนู Helpdesk
        $menu->add('helpdesk', '{LNG_Helpdesk}', null, $submenus);
        $menu->addTopLvlMenu('helpdesk', '{LNG_Helpdesk}', null, $submenus, 'member');
        $submenus = [];
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission($login, 'can_config')) {
            $submenus[] = array(
                'text' => '{LNG_Module settings}',
                'url' => 'index.php?module=helpdesk-settings'
            );
        }
        if (Login::checkPermission($login, 'can_manage_helpdesk')) {
            foreach (Language::get('HELPDESK_STATUSES', []) as $type => $text) {
                $submenus[] = array(
                    'text' => $text,
                    'url' => 'index.php?module=helpdesk-statuses&amp;type='.$type
                );
            }
            foreach (Language::get('HELPDESK_CATEGORIES', []) as $type => $text) {
                $submenus[] = array(
                    'text' => $text,
                    'url' => 'index.php?module=helpdesk-categories&amp;type='.$type
                );
            }
        }
        if (!empty($submenus)) {
            $menu->add('settings', '{LNG_Helpdesk}', null, $submenus, 'helpdesk');
        }
    }
}
