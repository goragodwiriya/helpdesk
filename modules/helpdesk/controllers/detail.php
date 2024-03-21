<?php
/**
 * @filesource modules/helpdesk/controllers/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Detail;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายละเอียดการซ่อม
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // อ่านข้อมูลรายการที่ต้องการ
        $index = \Helpdesk\Detail\Model::get($request->request('id')->toInt());
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_View} {LNG_Ticket}');
        // เลือกเมนู
        $this->menu = 'helpdesk';
        // สมาชิก
        $login = Login::isMember();
        // ผู้เขียน ผู้ดูแล Agents
        if ($index && $login && ($login['id'] == $index->customer_id || Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent')))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-support">{LNG_Helpdesk}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=helpdesk-setup&id=0}">{LNG_Tickets}</a></li>');
            $ul->appendChild('<li><span>'.$index->ticket_no.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-file">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Helpdesk\Detail\View::create()->render($index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
