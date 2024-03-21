<?php
/**
 * @filesource modules/helpdesk/views/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Detail;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=helpdesk-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Helpdesk\Tools\View
{
    /**
     * @var \Helpdesk\Status\Model
     */
    private $statuses;
    /**
     * @var \Helpdesk\Category\Model
     */
    private $category;

    /**
     * แสดงรายละเอียดการซ่อม
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $this->category = \Helpdesk\Category\Model::init();
        $statuses = \Helpdesk\Status\Model::create();
        // Reply
        $replys = '';
        $isCustomer = $index->customer_id != $login['id'];
        foreach (\Helpdesk\Detail\Model::replys($index, $isCustomer) as $item) {
            if (self::$cfg->helpdesk_closed_status == $index->status) {
                $isOwner = 0;
            } else {
                $isOwner = $item->member_id == $login['id'] || Login::checkPermission($login, 'can_manage_helpdesk') ? $login['id'] : 0;
            }
            $replys .= '<section class="helpdesk setup_frm">';
            $replys .= '<div class="detail">';
            $replys .= self::highlighter($item->comment);
            $replys .= '<div class="files">'.\Download\Index\Controller::init($item->id, 'helpdesk', self::$cfg->helpdesk_img_typies, $isOwner).'</div>';
            $replys .= '</div><footer class=clear>';
            $replys .= '<span class=icon-calendar>'.Date::format($item->create_date, 'd M Y H:i').'</span>';
            $replys .= '<span class="icon-'.($item->member_id == $index->customer_id ? 'user' : 'customer').' status'.$item->ustatus.'">'.$item->name.'</span>';
            if ($item->private == 1) {
                $replys .= '<span class="icon-valid color-green">{LNG_Private}</span>';
            }
            $replys .= '</footer></section>';
        }
        if (self::$cfg->helpdesk_closed_status == $index->status) {
            $replyform = '<div class="reopen"><button id="reopen_'.$index->id.'" class="icon-support button large" style="color:#FFF;background-color:%STATUS_COLOR%">{LNG_Reopen Ticket}</button></div>';
            $isOwner = 0;
        } else {
            $replyform = $this->replyForm($index, $login);
            $isOwner = $index->customer_id == $login['id'] || Login::checkPermission($login, 'can_manage_helpdesk') ? $login['id'] : 0;
        }
        // template
        $template = Template::createFromFile(ROOT_PATH.'modules/helpdesk/views/detail.html');
        $template->add(array(
            '/%NAME%/' => $index->name,
            '/%USER_STATUS%/' => $index->ustatus,
            '/%PHONE%/' => $index->phone,
            '/%SUBJECT%/' => $index->subject,
            '/%TICKET_NO%/' => $index->ticket_no,
            '/%DETAIL%/' => self::highlighter($index->detail),
            '/%CREATE_DATE%/' => Date::format($index->create_date, 'd M Y H:i'),
            '/%TIMEAGO%/' => self::timeAgo($index->create_date),
            '/%CATEGORY%/' => $this->category->get('category', $index->category),
            '/%PRIORITY%/' => $statuses->priority($index->priority),
            '/%PRIORITY_COLOR%/' => $statuses->priorityColor($index->priority),
            '/%FILES%/' => \Download\Index\Controller::init($index->status_id, 'helpdesk', self::$cfg->helpdesk_img_typies, $isOwner),
            '/%REPLYFORM%/' => $replyform,
            '/%STATUS%/' => $statuses->status($index->status),
            '/%STATUS_COLOR%/' => $statuses->statusColor($index->status),
            '/%REPLYS%/' => $replys,
            '/%ID%/' => $index->id
        ));
        // คืนค่า HTML
        return $template->render();
    }

    /**
     * Reply Ticket
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    private function replyForm($index, $login)
    {
        // Agent
        $isAgent = Login::checkPermission($login, array('can_manage_helpdesk', 'helpdesk_agent'));
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/helpdesk/model/reply/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-reply',
            'title' => '{LNG_Reply}'
        ));
        // comment
        $fieldset->add('textarea', array(
            'id' => 'comment',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Message}',
            'placeholder' => '{LNG_BBCode support}',
            'rows' => 5
        ));
        $comment = Language::replace('Browse image uploaded, type :type', [':type' => implode(', ', self::$cfg->helpdesk_img_typies)]);
        $comment .= ' ({LNG_You can select multiple files}, {LNG_resized automatically})';
        // helpdesk
        $fieldset->add('file', array(
            'id' => 'helpdesk',
            'name' => 'helpdesk[]',
            'labelClass' => 'g-input icon-gallery',
            'itemClass' => 'item',
            'label' => '{LNG_Attach file}',
            'comment' => $comment,
            'dataPreview' => 'imgPicture',
            'multiple' => true,
            'accept' => self::$cfg->helpdesk_img_typies
        ));
        // status
        if ($isAgent) {
            $ticketstatus = $this->category->toSelect('ticketstatus');
        } else {
            $ticketstatus = [];
            foreach ($this->category->toSelect('ticketstatus') as $status => $label) {
                if ($status == $index->status || $status == self::$cfg->helpdesk_closed_status) {
                    $ticketstatus[$status] = $label;
                }
            }
        }
        $fieldset->add('select', array(
            'id' => 'status',
            'labelClass' => 'g-input icon-subcategory',
            'itemClass' => 'item',
            'label' => $this->category->name('ticketstatus'),
            'options' => $ticketstatus,
            'value' => $index->status
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'icon-save button ok large',
            'id' => 'order_submit',
            'value' => '{LNG_Reply}'
        ));
        if ($isAgent) {
            // private
            $fieldset->add('checkbox', array(
                'id' => 'private',
                'label' => '&nbsp;{LNG_Private}',
                'labelClass' => 'inline-block middle margin-left',
                'value' => 1
            ));
        }
        // id
        $fieldset->add('hidden', array(
            'id' => 'helpdesk_id',
            'value' => $index->id
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
