<?php
/**
 * @filesource modules/helpdesk/views/statuswrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Statuswrite;

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
class View extends \Gcms\View
{
    /**
     * ฟอร์มเพิ่ม/แก้ไข Status
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/helpdesk/model/statuswrite/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $form->add('header', array(
            'innerHTML' => '<h3 class=icon-star0>'.Language::get('HELPDESK_STATUSES', ucfirst($index->type), $index->type).'</h3>'
        ));
        $fieldset = $form->add('fieldset');
        // category_id
        $fieldset->add('number', array(
            'id' => 'status_category_id',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-number',
            'label' => 'ID',
            'value' => $index->category_id
        ));
        // color
        $fieldset->add('color', array(
            'id' => 'status_color',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-color',
            'label' => '{LNG_Color}',
            'value' => isset($index->color) ? $index->color : null
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'status_topic',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => '{LNG_Status}',
            'maxlength' => 50,
            'value' => isset($index->topic) ? $index->topic : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'status_id',
            'value' => $index->id
        ));
        // type
        $fieldset->add('hidden', array(
            'id' => 'status_type',
            'value' => $index->type
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
