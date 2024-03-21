<?php
/**
 * @filesource modules/helpdesk/controllers/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Receive;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=helpdesk-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เพิ่ม-แก้ไข ใบรับงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // เลือกเมนู
        $this->menu = 'helpdesk';
        // ข้อความ title bar
        $this->title = Language::get('Create ticket');
        // สมาชิก
        if ($login = Login::isMember()) {
            // อ่านข้อมูลรายการที่ต้องการ
            $index = \Helpdesk\Receive\Model::get(
                $request->request('id')->toInt(),
                $request->request('category')->topic()
            );
            // ใหม่
            if ($index) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-support">{LNG_Helpdesk}</span></li>');
                if ($index->id > 0) {
                    // ข้อความ title bar
                    $this->title = Language::get('Helpdesk job description');
                    $ul->appendChild('<li><a href="{BACKURL?module=helpdesk-history}">{LNG_My tickets}</a></li>');
                }
                $ul->appendChild('<li><span>'.$this->title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // แสดงฟอร์ม
                $div->appendChild(\Helpdesk\Receive\View::create()->render($index, $login));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
