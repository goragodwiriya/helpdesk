<?php
/**
 * @filesource modules/helpdesk/controllers/history.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\History;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-history
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการ Ticket (User)
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // ค่าที่ส่งมา
        $params = array(
            'from' => $request->request('from')->date(),
            'to' => $request->request('to')->date(),
            'priority' => $request->request('priority')->toInt(),
            'category' => $request->request('category')->toInt(),
            'status' => $request->request('status')->toInt(),
            'customer_id' => $login['id']
        );
        // ข้อความ title bar
        $this->title = Language::get('My tickets');
        // เลือกเมนู
        $this->menu = 'helpdesk';
        // สมาชิก
        if ($login) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-support">{LNG_Helpdesk}</span></li>');
            $ul->appendChild('<li><span>{LNG_My tickets}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Helpdesk\History\View::create()->render($request, $params));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
