<?php
/**
 * @filesource modules/helpdesk/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Settings;

use Kotchasan\Html;

/**
 * module=helpdesk-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตั้งค่าโมดูล
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/helpdesk/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        $helpdesk_status = \Helpdesk\Status\Model::create()->toSelect('ticketstatus');
        // helpdesk_first_status
        $fieldset->add('select', array(
            'id' => 'helpdesk_first_status',
            'labelClass' => 'g-input icon-tools',
            'itemClass' => 'item',
            'label' => '{LNG_Initial ticket status}',
            'options' => $helpdesk_status,
            'value' => self::$cfg->helpdesk_first_status
        ));
        // helpdesk_closed_status
        $fieldset->add('select', array(
            'id' => 'helpdesk_closed_status',
            'labelClass' => 'g-input icon-tools',
            'itemClass' => 'item',
            'label' => '{LNG_Ticket status finished}',
            'options' => $helpdesk_status,
            'value' => self::$cfg->helpdesk_closed_status
        ));
        // helpdesk_reopened_status
        $fieldset->add('select', array(
            'id' => 'helpdesk_reopened_status',
            'labelClass' => 'g-input icon-tools',
            'itemClass' => 'item',
            'label' => '{LNG_Reopened ticket status}',
            'options' => $helpdesk_status,
            'value' => self::$cfg->helpdesk_reopened_status
        ));
        $comment = '{LNG_Prefix, if changed The number will be counted again. You can enter %Y%M (year, month).}';
        $comment .= ', {LNG_Number such as %04d (%04d means 4 digits, maximum 11 digits)}';
        $groups = $fieldset->add('groups', array(
            'comment' => $comment
        ));
        // helpdesk_prefix
        $groups->add('text', array(
            'id' => 'helpdesk_prefix',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Prefix}',
            'placeholder' => 'TICKET%Y%M-',
            'value' => self::$cfg->helpdesk_prefix
        ));
        // helpdesk_no
        $groups->add('text', array(
            'id' => 'helpdesk_no',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Ticket No.}',
            'placeholder' => '%04d, TICKET%04d',
            'value' => self::$cfg->helpdesk_no
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-thumbnail',
            'title' => '{LNG_Size of} {LNG_Image}'
        ));
        // helpdesk_w
        $fieldset->add('text', array(
            'id' => 'helpdesk_w',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'item',
            'label' => '{LNG_Width}',
            'comment' => '{LNG_Image size is in pixels} ({LNG_resized automatically})',
            'value' => self::$cfg->helpdesk_w
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
