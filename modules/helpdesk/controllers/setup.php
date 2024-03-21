<?php
/**
 * @filesource modules/helpdesk/controllers/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Setup;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการ Ticket (Admin)
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $params = array(
            'from' => $request->request('from')->date(),
            'to' => $request->request('to')->date(),
            'priority' => $request->request('priority')->toInt(),
            'category' => $request->request('category')->toInt(),
            'status' => $request->request('status')->toInt()
        );
        // สมาชิก
        $login = Login::isMember();
        // สามารถบริหารจัดการได้
        $isAdmin = Login::checkPermission($login, 'can_manage_helpdesk');
        // Agent
        $agents = [];
        if ($isAdmin) {
            $agents[0] = '{LNG_all items}';
            $params['agent_id'] = $request->request('agent_id')->toInt();
        } else {
            $params['agent_id'] = $login['id'];
        }
        foreach (\Helpdesk\Agent\Model::create()->toSelect() as $k => $v) {
            if ($isAdmin || $k == $login['id']) {
                $agents[$k] = $v;
            }
        }
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Tickets} ({LNG_Agent})');
        // เลือกเมนู
        $this->menu = 'helpdesk';
        // สามารถบริหารจัดการได้, Agent
        if (Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent'))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-support">{LNG_Helpdesk}</span></li>');
            $ul->appendChild('<li><span>{LNG_Tickets}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Helpdesk\Setup\View::create()->render($request, $params, $agents));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
