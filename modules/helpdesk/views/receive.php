<?php
/**
 * @filesource modules/helpdesk/views/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Receive;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=helpdesk-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * เพิ่ม-แก้ไข Ticket
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/helpdesk/model/receive/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-support',
            'title' => '{LNG_Create ticket}'
        ));
        // category
        $category = \Helpdesk\Category\Model::init();
        foreach ($category->items() as $key => $label) {
            if ($key !== 'ticketstatus') {
                $fieldset->add('select', array(
                    'id' => $key,
                    'labelClass' => 'g-input icon-subcategory',
                    'itemClass' => 'item',
                    'label' => $label,
                    'options' => array('' => '{LNG_Please select}') + $category->toSelect($key),
                    'value' => isset($index->{$key}) ? $index->{$key} : ''
                ));
            }
        }
        // subject
        $fieldset->add('text', array(
            'id' => 'subject',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Subject}',
            'maxlength' => 150,
            'value' => isset($index->subject) ? $index->subject : ''
        ));
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'placeholder' => '{LNG_BBCode support}',
            'rows' => 5,
            'value' => isset($index->detail) ? $index->detail : ''
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
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'id' => 'save',
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
