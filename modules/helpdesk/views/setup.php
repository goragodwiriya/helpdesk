<?php
/**
 * @filesource modules/helpdesk/views/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=helpdesk-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Helpdesk\Tools\View
{
    /**
     * @var obj
     */
    private $statuses;
    /**
     * @var obj
     */
    private $agents;
    /**
     * @var obj
     */
    private $categories;

    /**
     * Tickets (Agent)
     *
     * @param Request $request
     * @param array   $params
     * @param array   $agents
     *
     * @return string
     */
    public function render(Request $request, $params, $agents)
    {
        // สถานะ Ticket
        $this->statuses = \Helpdesk\Status\Model::create();
        // หมวดหมู่
        $this->categories = \Helpdesk\Category\Model::init();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            'class' => 'helpdesk',
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Helpdesk\Setup\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('helpdeskSetup_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('helpdeskSetup_sort', 'create_date desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'customer', 'customer_id', 'priority', 'create_date', 'category', 'latest', 'ticket_no', 'agents'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('customer', 'ticket_no', 'subject'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/helpdesk/model/setup/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'type' => 'date',
                    'name' => 'from',
                    'text' => '{LNG_from}',
                    'value' => $params['from']
                ),
                array(
                    'type' => 'date',
                    'name' => 'to',
                    'text' => '{LNG_to}',
                    'value' => $params['to']
                ),
                array(
                    'name' => 'category',
                    'text' => $this->categories->name('category'),
                    'options' => array(0 => '{LNG_all items}') + $this->categories->toSelect('category'),
                    'value' => $params['category']
                ),
                array(
                    'name' => 'agent_id',
                    'text' => '{LNG_Assign to}',
                    'options' => $agents,
                    'value' => $params['agent_id']
                ),
                array(
                    'name' => 'priority',
                    'text' => $this->statuses->name('ticketpriority'),
                    'options' => array(0 => '{LNG_all items}') + $this->statuses->toSelect('ticketpriority'),
                    'value' => $params['priority']
                ),
                array(
                    'name' => 'status',
                    'text' => $this->statuses->name('ticketstatus'),
                    'options' => array(0 => '{LNG_all items}') + $this->statuses->toSelect('ticketstatus'),
                    'value' => $params['status']
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'subject' => array(
                    'text' => ''
                ),
                'status' => array(
                    'text' => '{LNG_Status}',
                    'class' => 'right'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'subject' => array(
                    'class' => ''
                ),
                'status' => array(
                    'class' => 'top right'
                )
            )
        ));
        // save cookie
        setcookie('helpdeskSetup_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('helpdeskSetup_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        if (is_file(ROOT_PATH.DATA_FOLDER.'avatar/'.$item['customer_id'].'.jpg')) {
            $img = WEB_URL.DATA_FOLDER.'avatar/'.$item['customer_id'].'.jpg';
            $img = '<img src="'.$img.'" alt="Avatar">';
        } elseif (preg_match('/^.{2}/u', $item['customer'], $match)) {
            $img = '<span data-letters="'.$match[0].'">';
        }
        $subject = '<div class=subject>';
        $subject .= '<div class=usericon>'.$img.'</div>';
        $subject .= '<div>';
        $subject .= '<small class=no>#'.$item['ticket_no'].'</small> '.$item['customer'];
        $subject .= '<a class="one_line" href="'.WEB_URL.'index.php?module=helpdesk-detail&amp;id='.$item['id'].'" title="'.$item['subject'].'">'.$item['subject'].'</a>';
        $subject .= '<span class=icon-subcategory>'.$this->categories->get('category', $item['category']).'</span>';
        $subject .= '<span class=icon-calendar>'.Date::format($item['create_date'], 'd M Y H:i').'</span>';
        $subject .= '<span class=priority style="--bg-color:'.$this->statuses->priorityColor($item['priority']).'">'.$this->statuses->priority($item['priority']).'</span>';
        $subject .= '<span class=icon-customer>'.$item['agents'].'</span>';
        $subject .= '</div>';
        $item['subject'] = $subject;
        $latest = '<mark class="term" style="background-color:'.$this->statuses->statusColor($item['status']).'">'.$this->statuses->status($item['status']).'</mark><br>';
        $item['status'] = $latest.'<small>'.Date::format($item['latest'], 'd M Y H:i').'</small>';
        $item['status'] .= '<br><a class="icon-delete color-red notext" id=delete_'.$item['id'].' title="{LNG_Delete}"></a>';
        return $item;
    }
}
