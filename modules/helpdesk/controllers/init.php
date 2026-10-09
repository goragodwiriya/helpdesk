<?php
/**
 * @filesource modules/helpdesk/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Init;

use Gcms\Api as ApiController;

/**
 * ลงทะเบียนเมนูและสิทธิ์ของโมดูลศูนย์ช่วยเหลือ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สิทธิ์ของโมดูล (ใช้ชื่อเดียวกับระบบเดิม ผู้ใช้เดิมจึงไม่ต้องตั้งค่าใหม่)
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = [
            'value' => 'can_manage_helpdesk',
            'text' => '{LNG_Can manage the} {LNG_Helpdesk}'
        ];
        $permissions[] = [
            'value' => 'helpdesk_agent',
            'text' => '{LNG_Agent} ({LNG_Helpdesk})'
        ];

        return $permissions;
    }

    /**
     * เมนูของโมดูล
     * เมนูแจ้งเรื่องเห็นได้ทุกคนที่ล็อกอิน เหมือนระบบเดิม
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        $children = [
            [
                'title' => '{LNG_Create ticket}',
                'url' => '/helpdesk-receive?id=0',
                'icon' => 'icon-write'
            ],
            [
                'title' => '{LNG_My tickets}',
                'url' => '/helpdesk',
                'icon' => 'icon-list'
            ]
        ];
        if (ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
            $children[] = [
                'title' => '{LNG_Tickets} ({LNG_Agent})',
                'url' => '/helpdesk-jobs',
                'icon' => 'icon-customer'
            ];
        }
        $menus = parent::insertMenuAfter($menus, [
            [
                'title' => '{LNG_Helpdesk}',
                'icon' => 'icon-support',
                'children' => $children
            ]
        ], 'dashboard');

        // เมนูตั้งค่า
        $settings = [];
        if (ApiController::hasPermission($login, 'can_config')) {
            $settings[] = [
                'title' => '{LNG_Module settings}',
                'url' => '/helpdesk-settings',
                'icon' => 'icon-cog'
            ];
        }
        if (ApiController::hasPermission($login, 'can_manage_helpdesk')) {
            foreach (\Helpdesk\Category\Model::typies() as $type => $label) {
                $settings[] = [
                    'title' => $label,
                    'url' => '/helpdesk-categories?type='.$type,
                    'icon' => $type === 'category' ? 'icon-subcategory' : 'icon-star0'
                ];
            }
        }
        if (!empty($settings)) {
            $menus = parent::insertMenuChildren($menus, [
                [
                    'title' => '{LNG_Helpdesk}',
                    'icon' => 'icon-support',
                    'children' => $settings
                ]
            ], 'settings', null, 1);
        }

        return $menus;
    }
}
