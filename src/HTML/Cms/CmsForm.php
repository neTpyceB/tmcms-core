<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use TMCms\HTML\Cms\Element\CmsButton;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsCheckboxList;
use TMCms\HTML\Cms\Element\CmsInputHidden;
use TMCms\HTML\Cms\Element\CmsMultipleSelect;
use TMCms\HTML\Cms\Element\CmsRow;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Widget\SitemapPages;
use TMCms\HTML\Element;
use TMCms\HTML\Form;
use TMCms\Orm\Entity;
use TMCms\Routing\Languages;
use TMCms\Strings\Translations;
use TMCms\Strings\UID;

defined('INC') or exit;

class CmsForm extends Form
{
    private static $default_form_icon = 'edit';

    private $form_icon = '';
    private $form_title = '';
    private $redirect_to_current_ref = false;

    protected $full_view_enabled = true;

    private $form_id = '';
    private $form_data = [];
    private $translation_fields = [];
    private $translation_tabs = [];
    private $contains_backup = false;
    private $form_tag_output_enabled = true;
    private $use_ajax_for_post = false;
    private $ajax_callback;
    private $collapsed = false;
    private $used_field_blocks = false;
    private $show_submit_on_top = false;

    /**
     * @var CmsButton
     */
    private $button_submit;
    /**
     * @var CmsButton
     */
    private $button_cancel;

    /**
     * @param string $javascript
     *
     * @return $this
     */
    public function setAjaxCallbackFunction($javascript)
    {
        $this->ajax_callback = $javascript;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableAjax()
    {
        $this->use_ajax_for_post = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAjax()
    {
        $this->use_ajax_for_post = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableFormTagOutput()
    {
        $this->form_tag_output_enabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableFormTagOutput()
    {
        $this->form_tag_output_enabled = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFormTagOutputEnabled(): bool
    {
        return $this->form_tag_output_enabled;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->fields;
    }

    /**
     * Fields for fieldset
     *
     * @param string $title
     * @param array  $fields = array('name' => 'Title', 'field' => instance of CmsField);
     * @param bool   $collapsible
     *
     * @return $this
     */
    public function addFieldBlock(string $title, array $fields, bool $collapsible = false)
    {
        $this->used_field_blocks = true;

        $uid = UID::uid32();

        // copy form instance
        $form = clone $this;

        $form->fields = [];
        $form->translation_fields = [];

        // Add all fields to temp form
        foreach ($fields as $field_info) {
            $form->addField($field_info['name'], $field_info['field']);
        }

        // Create row with temp form inputs
        $this->addField('', CmsRow::getInstance('field_block_' . $uid)->setValue(
            '<fieldset class="' . ($collapsible ? 'collapsible_fieldset' : '') . ' "><legend>' . $title . '</legend>' . $form->outputFormTable() . '</fieldset>'
        ));

        return $this;
    }

    /**
     * @param string  $title
     * @param Element $field
     * @param array   $row_attributes
     *
     * @return $this
     */
    public function addField(string $title, Element $field, array $row_attributes = [])
    {
        $this->setContainsBackup($field);

        if ($field->isTranslationEnabled()) {
            $id = $field->getId();

            $this->translation_fields[$id] = [
                'title'          => $title,
                'field'          => $field,
                'row_attributes' => $row_attributes,
            ];
        } else {
            $this->setFieldValue($field);

            $this->fields[] = new CmsFormElement($title, $field, $row_attributes);
        }

        return $this;
    }

    /**
     * @param Element $field
     */
    private function setContainsBackup(Element $field)
    {
        if (!$this->contains_backup && $field->getBackup()) {
            if ($this->button_submit) {
                $this->button_submit->setOnclick('HTMLGen.storage.save();' . $this->button_submit->getOnClick());
            }

            $this->contains_backup = true;
        }
    }

    /**
     * @param Element $field
     */
    private function setFieldValue(Element $field)
    {
        $field_name = $field->getName();

        // Existing data from form
        $value = $this->form_data[$field_name] ?? '';

        // Replace brackets
        if(is_scalar($value)) {
            $value = $this->parseBracketData($value);
        }

        // Do something only if we have something to do with
        if ($value) {
            // Set selected choices
            if ($field instanceof CmsSelect || $field instanceof CmsMultipleSelect) {
                $field->setSelected($value);
            } elseif ($field instanceof CmsCheckbox) {
                // Selected choices
                $field->setChecked(true);
            } elseif (is_array($value) && $field instanceof CmsCheckboxList) {
                $data = [];

                foreach ($value as $k => $v) {
                    if ($v) $data[] = $k;
                }

                $field->setChecked($data);
            } else {
                // Other fields
                $field->setValue($value);
            }
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function parseBracketData($value): string
    {
        // Maybe we do not need to parse anything
        if (strpos($value, '{%') === false) {
            return $value;
        }

        $paired_data = [];
        foreach ($this->form_data as $k => $v) {
            $paired_data['{%' . $k . '%}'] = $v;
        }

        return strtr($value, $paired_data);
    }

    /**
     * @return string
     */
    private function outputFormTable(): string
    {
        $this->prepareForOutput();

        ob_start();

        ?>
        <table border="0">
            <col>
            <col>
        <col>
        <?php /* @var $field CmsFormElement */
        foreach ($this->fields as $field): ?>
            <?= $this->getRowOutput($field) ?>
        <?php endforeach; ?>
        </table><?php

        $this->outputInvisibleElements();

        return ob_get_clean();
    }

    /**
     * Handler that should be called to prepare all form data for output in browser
     */
    private function prepareForOutput()
    {
        $languages = Languages::getPairs();

        foreach ($this->translation_fields as $id => $data) {

            /** @var Element $field */
            $field = $data['field'];

            $name = $field->getName();

            if ($id && isset($this->form_data[$id])) {
                $values = is_array($this->form_data[$id]) ? $this->form_data[$id] : Translations::get($this->form_data[$id]);
            } elseif ($field->getValueArray()) {
                // for selects
                $values = $field->getValueArray();
            } else {
                // For supplied translation id
                $values = is_array($field->getValue()) ? $field->getValue() : Translations::get($field->getValue());
            }

            foreach ($languages as $key => $value) {
                $translation_field = clone $field;
                $translation_field->setName($name . '[' . $key . ']');
                $translation_field->setId($id . '_' . $key . '_');

                if ($translation_field->getWidget()) {
                    $translation_field->setWidget(clone $translation_field->getWidget());

                    $widget = $translation_field->getWidget();

                    if ($widget) {
                        // Set special cases here
                        if ($widget instanceof SitemapPages) {
                            /** @var SitemapPages $widget */
                            $widget->setLanquage($key);
                        }
                    }
                }

                if ($values && isset($values[$key])) {
                    $translation_field->setValue($values[$key]);
                }

                // Add translation input in tab
                $this->translation_tabs[$key][] = new CmsFormElement($data['title'], $translation_field, $data['row_attributes']);
            }
        }
    }

    /**
     * @param CmsFormElement $field
     *
     * @return string
     */
    private function getRowOutput(CmsFormElement $field): string
    {
        $element = $field->getElement();

        if ($element->isInvisible()) {
            // Return nothing for hidden elements
            return '';
        }

        $element_class = get_class($element);

        $id = $element->getId();
        $hint = $element->getHintText();

        ob_start();

        switch ($element_class) {
            // No form field, just value
            case CmsRow::class:
                ?>
                <div class="form-body row" <?= $field->getRowAttributesString() ?>>
                    <div class="col-md-12">
                        <?= $element ?>
                        <?php if ($hint): ?>
                            <span class="help-block"><?= $hint ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php

                break;

            // Usual form field
            default:
                // Widget
                $widget = $element->getWidget();
                // Do we need more space for widget button?

                $cols = [10, 0];
                if ($widget) {
                    $cols = [8, 2];
                }
                ?>
                <div class="form-body" <?= $field->getRowAttributesString() ?><?= $id ? ' id="' . $id . '_data"' : '' ?>>
                    <div class="form-group">
                        <label class="control-label col-md-2"><?= __($field->getLabel()) ?><?= ($element->isFieldRequired() ? ' <span class="required">* </span>' : '') ?></label>
                        <div class="col-md-<?= $cols[0] ?>">
                            <?= $element ?>
                            <?php if ($hint): ?>
                                <span class="help-block"><?= $hint ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($widget): ?>
                            <div class="col-md-<?= $cols[1] ?>">
                                <?= $widget ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php

                break;
        }

        return ob_get_clean();
    }

    /**
     * Echoes all fields that are hidden
     */
    private function outputInvisibleElements()
    {
        foreach ($this->fields as $field) {
            /* @var $field CmsFormElement */
            $element = $field->getElement();

            if ($element->isInvisible()) {
                echo $element;
            }
        }
    }

    /**
     * @return $this
     */
    public function enableRedirectToCurrentRef()
    {
        $this->redirect_to_current_ref = true;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setFormId($id)
    {
        $this->form_id = $id;

        return $this;
    }

    /**
     * @param mixed $button_submit CmsButton or string
     *
     * @return $this
     */
    public function setButtonSubmit($button_submit)
    {
        // Create button from string
        if (is_string($button_submit)) {
            $button_submit = CmsButton::getInstance($button_submit);
        }

        $this->button_submit = $button_submit;

        return $this;
    }

    /**
     * Enable or disable duplicate submit button on top of form
     *
     * @param $flag
     *
     * @return $this
     */
    public function setShowSubmitOnTop($flag)
    {
        $this->show_submit_on_top = (bool)$flag;

        return $this;
    }

    /**
     * @param mixed $button_cancel CmsButton or String button title
     *
     * @return $this
     */
    public function setButtonCancel($button_cancel)
    {
        if (is_string($button_cancel)) {
            $button_cancel = CmsButton::getInstance($button_cancel);
        }

        $this->button_cancel = $button_cancel;

        return $this;
    }

    public function __toString()
    {
        // Make action as string
        if (is_array($this->action)) {
            if (!isset($this->action['p'])) {
                $this->action['p'] = P;
            }
            if (!isset($this->action['do'])) {
                $this->action['do'] = P_DO;
            }
            $this->action = '?' . http_build_query($this->action);
        }

        $this->prepareForOutput();

        if ($this->redirect_to_current_ref) {
            $this->addField('cms_go_after_submit', CmsInputHidden::getInstance('cms_go_after_submit')->setValue(REF));
        }

        if ($this->button_submit) {
            // Special for ColumnInput
            $this->button_submit->setOnclick('if (typeof table_form != \'undefined\') table_form.prepareForSubmit(); ' . $this->button_submit->getOnClick() . ' checkHiddenInvalid(this)');
        }

        $form_id = $this->form_id ?: 'html_cms_form_' . md5(uniqid((string)mt_rand(), true));

        $form_title = $this->getFormTitle();

        ob_start();

        $full_view = $this->isFullViewEnabled();

        ?>
        <?php if ($full_view): ?>
        <div class="portlet box<?= $form_title ? ' blue' : '' ?>">
            <?php if ($form_title): ?>
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-<?= $this->getFormIcon() ?>"></i><?= $form_title ?>
                    </div>
                    <div class="tools">
                        <a href="javascript:" class="<?= $this->collapsed ? 'expand' : 'collapse' ?>"></a>
                    </div>
                </div>
            <?php endif;
            endif; ?>
            <div class="portlet-body form<?= $this->collapsed ? ' portlet-collapsed' : '' ?>" style="display: <?= $this->collapsed ? 'none' : 'block' ?>;">
                <?php if ($this->form_tag_output_enabled): // Tag may be disabled for inline forms in tables ?>
                <form action="<?= $this->action ?>" method="<?= $this->method ?>" enctype="<?= $this->enctype ?>" id="<?= $form_id ?>" class="form-horizontal form-bordered form-row-stripped">
                    <?php endif;
                    if ($this->show_submit_on_top): ?>
                        <?= $this->button_submit ?>
                        <br>
                        <br>
                    <?php endif;
                    foreach ($this->fields as $field): /* @var $field CmsFormElement */
                        // Add onclick scripts
                        $script_onclick = $field->getElement()->getOnClick();
                        // Add ; at the end
                        if ($script_onclick && $script_onclick[strlen($script_onclick) - 1] !== ';') {
                            $script_onclick .= ';';
                        }
                        // Add new scripts
                        $field->getElement()->setOnclick($script_onclick);

                        // Output row
                        echo $this->getRowOutput($field);
                        ?>
                    <?php endforeach;

                    // Tabs for translational fields
                    if ($this->translation_tabs) {
                        // Tab view
                        $tabs = new CmsTabs;
                        $tabs->setCaptionTitle(__('Translations'));
                        foreach ($this->translation_tabs as $lng_key => $tab_data):
                            $tab_content = '';
                            foreach ($tab_data as $field): /* @var $field CmsFormElement */
                                $onkeyup = $field->getElement()->getOnkeyup();
                                if ($onkeyup && $onkeyup[strlen($onkeyup) - 1] !== ';') $onkeyup .= ';';
                                $field->getElement()->setOnkeyup($onkeyup);
                                unset($onkeyup);

                                $tab_content .= $this->getRowOutput($field);
                            endforeach;

                            $tabs->addTab($lng_key, $tab_content, $lng_key == LNG);
                        endforeach;

                        echo $tabs;
                    }

                    if ($this->button_cancel || $this->button_submit):

                        // If have ajax - show additional button "with redirect" without ajax
                        if ($this->isAjax()):
                            // Regular submit button
                            $regular_save_button = $this->button_submit;

                            $ajax_save_button = clone $regular_save_button;
                            $ajax_save_button->setId('button_to_not_ajaxify');
                            $ajax_save_button->setOnclick("no_need_ajax = true; this.form.action = this.form.action.replace('&ajax', '');this.form.submit();");

                            // Visual icon for ajax save
                            $ajax_save_button->setValue('<i class="fa fa-check"></i> ' . $ajax_save_button->getValue());

                            // Visual icon for regular save
                            $regular_save_button->setValue('<i class="fa fa-repeat"></i> ' . $regular_save_button->getValue());

                        // Keep in mind that save and ajax button replace vice versa for script purposes
                        else:
                            $regular_save_button = $this->button_submit;
                            $regular_save_button->setValue('<i class="fa fa-check"></i> ' . $regular_save_button->getValue());

                            $ajax_save_button = '';
                        endif; ?>
                        <div class="form-actions">
                            <div class="btn-set pull-left">
                                <?= $regular_save_button ?>
                                &nbsp;
                                <?= $ajax_save_button ?>
                                &nbsp;
                                <?php if ($this->button_cancel): ?>
                                    <button type="reset" class="btn red"
                                        <?= $this->button_cancel->getAttributesString() ?>
                                        <?= $this->button_cancel->getAttribute('onclick') ? '' : 'onclick="if(confirm(\'' . __('Any unsaved data will be lost. Are you sure?') . '\')){if(history.length>1)setTimeout(\'history.go(-1)\',25)}else return false;"' ?>
                                    ><?= $this->button_cancel->getValue() ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif;
                    // Some hidden inputs
                    $this->outputInvisibleElements();
                    ?>
                    <?php if ($this->form_tag_output_enabled): ?>
                </form>
            <?php endif;
            if ($this->used_field_blocks) {
                echo '<div class="clearfix"></div>';
            }
            ?>
            </div>
            <?php if ($full_view): ?>
        </div>
    <?php endif; ?>

        <?php

        // Ajaxify form
        if ($this->isAjax()): ?>
            <script>
                var no_need_ajax = false;
                var ajax_form_id = '<?=$form_id?>';
                var $form = jQuery('#' + ajax_form_id);
                var $button = $form.find('input[type=submit]').not('#button_to_not_ajaxify');
                var button_text = $button.val();
                var ajax_options = {
                    beforeSubmit: function () {
                        var is_valid = $form.parsley().isValid();
                        if (!is_valid) return false;

                        // Check if clicked on second button - do not send ajax, but submit as usual
                        if (no_need_ajax) {
                            return false;
                        }

                        $button.prop('disabled', true).val('<?= __('Please wait...') ?>').addClass('waiting_ajax');
                    },
                    success: function (text, status, xhr, $form) {
                        // Callback if required
                        <?php if ($this->ajax_callback): ?>
                            <?= $this->ajax_callback; ?>
                        <?php endif; ?>

                        if (status === 'success') {
                            $button.addClass('success_ajax').val('<?= __('Done') ?>');
                        } else {
                            $button.addClass('failed_ajax').val('<?= __('Failed') ?>');
                        }
                        setTimeout(function () {
                            $button.prop('disabled', false).val(button_text).removeClass('waiting_ajax').removeClass('success_ajax').removeClass('failed_ajax');
                        }, 2000);

                        // Request messages for Toaster
                        ajax_toasters.request_new_messages();
                    }
                };
                $form.attr('action', $form.attr('action') + '&ajax').ajaxForm(ajax_options);
            </script>
        <?php endif;
        if ($this->contains_backup) {
            $storage_uid = $this->form_id . $this->id . $this->action . $this->method . $this->enctype;
            echo '<script>HTMLGen.storage.init("', md5($storage_uid), '")</script>';
        }

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getFormTitle(): string
    {
        return $this->form_title;
    }

    /**
     * @return bool
     */
    public function isFullViewEnabled(): bool
    {
        return $this->full_view_enabled;
    }

    /**
     * @param boolean $full_view_enabled
     *
     * @return $this
     */
    public function setFullViewEnabled($full_view_enabled)
    {
        $this->full_view_enabled = $full_view_enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormIcon(): string
    {
        return $this->form_icon ?: self::$default_form_icon;
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return (bool)$this->use_ajax_for_post;
    }

    /**
     * @param array $data to be checked
     * @param bool $highlight_fields show on not errors in form after reload
     * @return array
     */
    public function validateAndGetErrors(array $data, $highlight_fields = false)
    {
        $errors = [];
        $_SESSION['cms_last_form_errors'] = '';

        /** @var CmsFormElement $field */
        foreach ($this->fields as $field) {
            /** @var Element $element */
            $element = $field->getElement();
            $attributes = $element->getAttributes();
            $name = $attributes['name'];
            if (isset($data[$name])) {
                $field_value = $data[$name];

                $err = Validation::validateFiled($field_value, $element->getValidatorBackendChecks());
                if(is_array($err)){
                    $errors[$name] = $err;
                }
            }
        }

        // Save errors in session to be shown in form after page reload
        if ($highlight_fields) {
            $_SESSION['cms_last_form_errors'] = json_encode($errors);
        }

        return $errors;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setFormTitle($title)
    {
        $this->form_title = $title;

        return $this;
    }

    public function setFormIcon($icon)
    {
        $this->form_icon = $icon;

        return $this;
    }

    public function setCollapsed($flag)
    {
        $this->collapsed = (bool)$flag;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableFullView()
    {
        $this->setFullViewEnabled(false);

        return $this;
    }

    public function enableRestoreFromPostBackup()
    {
        if (isset($_SESSION['saved_post'])) {
            $data = @json_decode($_SESSION['saved_post'], JSON_OBJECT_AS_ARRAY);
            if ($data && is_array($data)) {
                $this->addData($data);
            }
        }

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function addData($data)
    {
        if ($this->form_data) return $this;

        if (is_string($data)) {
            $data = q_assoc_row($data);
        } elseif (is_object($data) && $data instanceof Entity) {
            $data = $data->getAsArray();
        }
        $this->form_data = $data;
        /* @var $field CmsFormElement */
        foreach ($this->fields as $field) {
            $element = $field->getElement();
            $this->setFieldValue($element);
        }
        return $this;
    }
}