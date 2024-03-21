<?php
/**
 * @filesource modules/helpdesk/controllers/statuses.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Statuses;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-statuses
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สถานะ
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $params = array(
            // ประเภทที่ต้องการ
            'type' => $request->request('type')->topic(),
            // ชื่อหมวดหมู่ที่สามารถใช้งานได้
            'statuses' => Language::get('HELPDESK_STATUSES', [])
        );
        if (!isset($params['statuses'][$params['type']])) {
            $params['type'] = \Kotchasan\ArrayTool::getFirstKey($params['statuses']);
        }
        // ข้อความ title bar
        $title = $params['statuses'][$params['type']];
        $this->title = Language::trans('{LNG_List of} '.$title);
        // เลือกเมนู
        $this->menu = 'settings';
        // สามารถบริหารจัดการได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_helpdesk')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-settings">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_Helpdesk}</span></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-support">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'helpdesk'));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Helpdesk\Statuses\View::create()->render($request, $params, $title));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
