<?php

namespace TMCms\HTML\Cms;

use TMCms\DB\SQL;
use TMCms\HTML\Cms\Column\ColumnInput;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsCheckboxList;
use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Element\CmsInputColor;
use TMCms\HTML\Cms\Element\CmsInputDataList;
use TMCms\HTML\Cms\Element\CmsInputEmail;
use TMCms\HTML\Cms\Element\CmsInputFile;
use TMCms\HTML\Cms\Element\CmsInputHidden;
use TMCms\HTML\Cms\Element\CmsInputNumber;
use TMCms\HTML\Cms\Element\CmsInputPassword;
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsInputTextRandom;
use TMCms\HTML\Cms\Element\CmsMultipleSelect;
use TMCms\HTML\Cms\Element\CmsRadioBox;
use TMCms\HTML\Cms\Element\CmsRow;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Element\CmsTextarea;
use TMCms\HTML\Cms\Widget\Custom;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\HTML\Cms\Widget\SitemapPages;
use TMCms\HTML\Cms\Widget\SvgMap;
use TMCms\Orm\Entity;
use TMCms\Strings\Converter;

class CmsFormHelper {
    /**
     * @param $table
     * @param array $params [data, action, button, fields[], unset[], order[]]
     * @return CmsForm
     */
    public static function outputForm($table, array $params = []) {
        // Maybe only one argument
        if (!$params) {
            $params = $table;
        }

        if (isset($params['db_table'])) {
            $table = $params['db_table'];
        }

        // Convert data to array
        if (isset($params['data']) && is_object($params['data'])) {
            /** @var Entity $obj */
            $obj = $params['data'];

            $params['data'] = $obj->getAsArray();
        }

        if (!isset($params['fields'])) {
            $params['fields'] = [];
        } else {
            $params['fields'] = self::normalizeFields($params['fields']);
        }

        // Generate fields from DB and combine with provided params
        if (isset($params['combine']) && $params['combine']) {
            $sort_order = isset($params['order']) ? $params['order'] : array_keys($params['fields']);
            $params['fields'] = array_merge(self::combineParamsFromDB($table, $sort_order), $params['fields']);
        }

        if (isset($params['unset'])) {
            foreach ($params['unset'] as $v) {
                unset($params['fields'][$v]);
            }
        }

        // Generate form
        $form = new CmsForm;

        if (!isset($params['action'])) {
            $tmp = $_GET;
            $tmp['do'] = '_'. $tmp['do'];
            $params['action'] = urldecode('?' . http_build_query($tmp));
        }

        if (isset($params['no_tag'])) {
            $form->disableFormTagOutput();
        }

        if (isset($params['full'])) {
            $form->setFullViewEnabled($params['full']);
        }

        if (isset($params['action'])) {
            $form->setAction($params['action']);
        }

        if (isset($params['title'])) {
            $form->setFormTitle($params['title']);
        }
        if (isset($params['icon'])) {
            $form->setFormIcon($params['icon']);
        }

        if (isset($params['button'])) {
            $form->setButtonSubmit($params['button']);
        }

        if (isset($params['collapsed'])) {
            $form->setCollapsed($params['collapsed']);
        }

        if (isset($params['submit_on_top'])) {
            $form->setShowSubmitOnTop((bool)$params['submit_on_top']);
        }

        if (isset($params['ajax']) && $params['ajax']) {
            $form->enableAjax();
        }

        if (isset($params['ajax_callback']) && $params['ajax_callback']) {
            $form->setAjaxCallbackFunction($params['ajax_callback']);
        }

        if (isset($params['cancel']) && $params['cancel']) {
            if (is_bool($params['cancel'])) {
                $params['cancel'] = __('Cancel');
            }
            $form->setButtonCancel($params['cancel']);
        }

        if (isset($params['fields'])) {
            foreach ($params['fields'] as $key => $field) {
                if (!is_array($field)) {
                    $key = $field;
                }

                // Field label
                if (isset($field['title'])) {
                    $name = $field['title'];
                } elseif (isset($field['name'])) {
                    $name = $field['name'];
                } else {
                    $name = $key;
                    if (substr($name, -3) == '_id') {
                        $name = substr($name, 0, -3);
                    }
                    $name = Converter::charsToNormalTitle($name);
                }

                //
                if (isset($params['field_key_prefix'])) {
                    $key = $params['field_key_prefix'] . $key;
                }

                // Input type
                $cms_field = NULL;

                // Known types
                if (!isset($field['type']) && isset($field['options'])) $field['type'] = 'select';
                if (!isset($field['type']) && isset($field['checked'])) $field['type'] = 'checkbox';
                if (!isset($field['type']) && isset($field['rows'])) $field['type'] = 'textarea';

                // Set field object
                if (!isset($field['type']) || $field['type'] == 'text') {
                    $cms_field = CmsInputText::getInstance($key);
                } elseif ($field['type'] == 'select' && (!isset($field['multi']) || (isset($field['multi']) && !$field['multi']))) {
                    $cms_field = CmsSelect::getInstance($key);
                    // Selected value
                    if (isset($field['value'])) {
                        $cms_field->setSelected($field['value']);
                    }
                } elseif ($field['type'] == 'multiselect' || $field['type'] == 'multi' || (isset($field['multi']) && $field['multi'])) {
                    $cms_field = CmsMultipleSelect::getInstance($key);
                } elseif ($field['type'] == 'checkbox_list') {
                    $cms_field = CmsCheckboxList::getInstance($key);
                    // Set checked checkboxes in list
                    if (isset($params['data'][$key])) {
                        $field['checked'] = (array) $params['data'][$key];
                    }
                } elseif ($field['type'] == 'datetime' || $field['type'] == 'time' || $field['type'] == 'date') {
                    // Options for JS datepicker plugin
                    if (!isset($field['options'])) {
                        $field['options'] = [];
                    }

                    $cms_field = CmsInputText::getInstance($key)->enableDateTimePicker($field['options']);
                    // Default time

                    if (!isset($params['data'][$key])) {
                        $params['data'][$key] = NOW;
                    }

                    // Set value from db in required format from timestamp
                    if (isset($params['data'][$key]) && ctype_digit((string)$params['data'][$key])) {
                        $params['data'][$key] = date('Y-m-d H:i', $params['data'][$key]); // Convert ts to date
                    }

                    if (isset($field['format'])) {
                        $cms_field->setDateFormat($field['format']);
                    }
                } elseif ($field['type'] == 'minicolors' || $field['type'] == 'colorpicker') {
                    // Options for JS datepicker plugin
                    if (!isset($field['options'])) {
                        $field['options'] = [];
                    }
                    $cms_field = CmsInputText::getInstance($key)->enableMiniColors($field['options']);
                } elseif ($field['type'] == 'password') {
                    $cms_field = CmsInputPassword::getInstance($key);
                } elseif ($field['type'] == 'row') {
                    if (isset($field['value'])) {
                        $cms_field = CmsRow::getInstance($key)->setValue($field['value']);
                    }
                } elseif ($field['type'] == 'random') {
                    $cms_field = CmsInputTextRandom::getInstance($key);
                } elseif ($field['type'] == 'checkbox') {
                    $cms_field = CmsCheckbox::getInstance($key);
                    // checked box
                    if (isset($field['value']) && $field['value']) {
                        $cms_field->setChecked(true);
                    }
                } elseif ($field['type'] == 'radio_group') {
                    $cms_field = CmsRadioBox::getInstance($key);
                } elseif ($field['type'] == 'email') {
                    $cms_field = CmsInputEmail::getInstance($key);
                } elseif ($field['type'] == 'textarea' || $field['type'] == 'text') {
                    $cms_field = CmsTextarea::getInstance($key);
                } elseif ($field['type'] == 'number' || $field['type'] == 'digit' || $field['type'] == 'int') {
                    $cms_field = CmsInputNumber::getInstance($key);
                } elseif ($field['type'] == 'datalist') {
                    $cms_field = CmsInputDataList::getInstance($key);
                } elseif ($field['type'] == 'hidden') {
                    $cms_field = CmsInputHidden::getInstance($key);
                } elseif ($field['type'] == 'file') {
                    $form->setEnctypeMultipartFormData();
                    $cms_field = CmsInputFile::getInstance($key);
                } elseif ($field['type'] == 'color') {
                    $cms_field = CmsInputColor::getInstance($key);
                } elseif ($field['type'] == 'html') {
                    $cms_field = CmsHtml::getInstance($key);
                } elseif ($field['type'] == 'input_table') {
                    // Special case for own table with inputs
                    $cms_field = CmsHtml::getInstance($field['title']);

                    $input_table = CmsTable::getInstance($key);
                    if (isset($params['data'][$key], $params['data'][$key])) {
                        $input_table->addDataArray($params['data'][$key]);
                    }

                    // Attach fields to table
                    foreach ($field['fields'] as $input_field_key => $input_field_data) {
                        $input_field = ColumnInput::getInstance($input_field_key);

                        // Select field
                        if (!isset($input_field_data['type']) && isset($input_field_data['options'])) {
                            $input_field_data['type'] = 'select';
                        }

                        // Usual text
                        if (!isset($input_field_data['type'])) {
                            $input_field_data['type'] = 'text';
                        }

                        // Empty options if field is select
                        if ($input_field_data['type'] == 'select' && !isset($input_field_data['options'])) {
                            $input_field_data['options'] = [];
                        }

                        // Set title is present
                        if (isset($input_field_data['title'])) {
                            $input_field->setTitle($input_field_data['title']);
                        }

                        switch ($input_field_data['type']) {
                            case 'text':
                                $input_field->setTypeText();
                                break;

                            case 'select':
                                $input_field->setTypeSelect();
                                $input_field->setOptions($input_field_data['options']);
                                break;

                            case 'html':
                                $input_field->setTypeHtml();
                                break;
                        }

                        $input_field->getOnchange(' '); // No auto-submit

                        // Any script attached
                        if (isset($input_field_data['js_onchange'])) {
                            $input_field->getOnchange($input_field_data['js_onchange']);
                        }

                        $input_table->addColumn($input_field);
                    }

                    // Column to delete row
                    if (isset($field['delete']) && $field['delete']) {
                        $input_table->addColumn(ColumnInput::getInstance('delete')->setTypeDelete());
                    }

                    // Link to add new row
                    if (isset($field['add']) && $field['add']) {
                        $input_table->showAddRow(true);
                    }

                    $cms_field->setValue($input_table);

                } else {
                    dump('Field type "'. $field['type'] .'" not found');
                }

                if ($cms_field) {
                    // Extra params
                    if (isset($field['options']) && is_array($field['options']) && in_array($field['type'], ['select', 'multiselect', 'datalist'])) {
                        $cms_field->setOptions($field['options']);
                    }
                    if (isset($field['options']) && $field['type'] == 'checkbox_list') {
                        $cms_field->setCheckboxes($field['options']);
                    }
                    if (isset($field['buttons']) && $field['type'] == 'radio_group') {
                        $cms_field->setRadioButtons($field['buttons']);
                        if (isset($params['data'][$key])) {
                            $cms_field->setSelected($params['data'][$key]);
                        }
                    }
                    if (isset($field['checked'])) {
                        $cms_field->setChecked($field['checked']);
                    }
                    if (isset($field['value'])) {
                        $cms_field->setValue($field['value']);
                    }
                    if (isset($field['onchange'])) {
                        $cms_field->setOnchange($field['onchange']);
                    }
                    if (isset($field['selected'])) {
                        $cms_field->setSelected($field['selected']);
                    }
                    if (isset($field['multiple'])) {
                        $cms_field->setIsMultiple(true);
                    }
                    if (isset($field['translation'])) {
                        $cms_field->enableTranslation();
                    }
                    if (isset($field['required'])) {
                        $cms_field->validateRequired();
                    }
                    if (isset($field['uid_options'])){
                        $cms_field->setUid($field['uid_options']['source'],
                            (int)($field['uid_options']['max_uid_length'] ?? 255),
                            $field['uid_options']['connect_onload'] ?? false,
                            $field['uid_options']['connect_onchange'] ?? (isset($params['data']['id']) && $params['data']['id'])
                        );
                    }


                    // Disable custom css styles for select elements
                    if (isset($field['disable_custom_plugin'])) {
                        $cms_field->disableCustomStyled();
                    }

                    // Auto-generated slug
                    if (isset($field['uid']) && !isset($field['translation'])) {
                        $to = $field['uid'];
                        // Check if we need to change key for current language translation
                        if (isset($params['fields'][$field['uid']]['translation']) && $params['fields'][$field['uid']]['translation']) {
                            $to = $to . '_'. LNG .'_';
                        }

                        $cms_field->enableSlugGenerationUidFromField($key, $to);
                    }
                    if (isset($field['readonly']) && $field['readonly']) {
                        $cms_field->enableReadOnly();
                    }
                    if (isset($field['html']) && $field['html']) {
                        $cms_field->allowHtml();
                    }
                    if (isset($field['rows'])) {
                        $cms_field->setRowCount($field['rows']);
                    }
                    if (isset($field['backup'])) {
                        $cms_field->setBackup($field['backup']);
                    }
                    if (isset($field['hint'])) {
                        $cms_field->setHintText($field['hint']);
                    }
                    if (isset($field['min'])) {
                        $cms_field->setMin($field['min']);
                    }
                    if (isset($field['max'])) {
                        $cms_field->setMax($field['max']);
                    }
                    if (isset($field['min-symbols'])) {
                        $cms_field->setAttribute('data-min', $field['min-symbols']);
                    }
                    if (isset($field['max-symbols'])) {
                        $cms_field->setAttribute('data-max', $field['max-symbols']);
                    }
                    if (isset($field['step'])) {
                        $cms_field->setStep($field['step']);
                    }
                    if (isset($field['reveal'])) {
                        $cms_field->setReveal((bool)$field['reveal']);
                    }
                    if (isset($field['maxlength'])) {
                        $cms_field->maxlength($field['maxlength']);
                    }
                    if (isset($field['disabled'])) {
                        $cms_field->setDisabled();
                    }

                    // Editors
                    if (isset($field['edit'])) {
                        switch($field['edit']) {
                            default:
                                dump('Widget for edit type "'. $field['edit'] .'" not found');
                                break;

                            // Visual editor
                            case 'wysiwyg':
                                $cms_field->enableWysiwyg();
                                if(!empty($field['wysiwyg_options'])){
                                    $cms_field->getWidget()->wysiwyg_options = $field['wysiwyg_options'];
                                }
                                break;

                            // Google map for choosing coordinates
                            case 'map':
                                $cms_field->enableGoogleMap(!empty($field['map_options']) ? $field['map_options'] : []);
                                break;

                            // Structure pages
                            case 'pages':
                                $sitemap = new SitemapPages();
                                if(!empty($field['sitemap_options'])){
                                    $sitemap->options = $field['sitemap_options'];
                                }
                                $cms_field->setWidget($sitemap);
                                break;

                            // SVG image handling for choosing polygon section
                            case 'svg_map':
                                // We need path to set for widget
                                if (!isset($params['fields'][$key . '_path'])) {
                                    dump('Form must have field "'. $key . '_path" with path for svg image.');
                                }

                                $path = '';

                                if (isset($params['data'][$key . '_path'])) {
                                    $path = $params['data'][$key . '_path'];
                                }

                                $svg_map = new SvgMap();
                                $svg_map->setSvgImagePath($path);

                                $cms_field->setWidget($svg_map);
                                break;

                            // Integrated filemanager
                            case 'files':
                            case 'filemanager':
                                $file_manager = FileManager::getInstance();

                                // Default path for filemanager
                                if (isset($field['path'])) {
                                    $file_manager->setPath($field['path']);
                                }

                                // Allow upload of only these extensions
                                if (isset($field['allowed_extensions'])) {
                                    $file_manager->setAllowedExtensions($field['allowed_extensions']);
                                }

                                // Refresh page after filemanager is closed
                                if (isset($field['reload'])) {
                                    $file_manager->enablePageReloadOnClose();
                                }

                                $cms_field->setWidget($file_manager);
                                break;

                            // Structure pages
                            case 'custom':
                                $widget = Custom::getInstance();

                                if (isset($field['url'])) {
                                    $widget->setModalPopupAjaxUrl($field['url']);
                                }

                                $cms_field->setWidget($widget);

                                break;
                        }
                    }

                    // Validators
                    if (isset($field['validate'])) {
                        if (isset($field['validate']['required']) || in_array('required', $field['validate'], true)) {
                            $cms_field->validateRequired();
                        }
                        if (isset($field['validate']['is_digit']) || in_array('is_digit', $field['validate'], true)) {
                            $cms_field->validateDigits();
                        }
                        if (isset($field['validate']['number']) || in_array('number', $field['validate'], true)) {
                            $cms_field->validateNumber();
                        }
                        if (isset($field['validate']['alphanum']) || in_array('alphanum', $field['validate'], true)) {
                            $cms_field->validateAlphaNumeric();
                        }
                        if (isset($field['validate']['url']) || in_array('url', $field['validate'], true)) {
                            $cms_field->validateUrl();
                        }
                        if (isset($field['validate']['email']) || in_array('email', $field['validate'], true)) {
                            $cms_field->validateEmail();
                        }
                        if (isset($field['validate']['min']) || in_array('min', $field['validate'], true)) {
                            $cms_field->validateMin($field['validate']['min']);
                        }
                        if (isset($field['validate']['max']) || in_array('max', $field['validate'], true)) {
                            $cms_field->validateMax($field['validate']['max']);
                        }
                        if (isset($field['validate']['minlength']) || in_array('minlength', $field['validate'], true)) {
                            $cms_field->validateMinLength($field['validate']['minlength']);
                        }
                        if (isset($field['validate']['maxlength']) || in_array('maxlength', $field['validate'], true)) {
                            $cms_field->validateMaxLength($field['validate']['maxlength']);
                        }
                    }

                    $form->addField($name, $cms_field);
                }
            }
        }

        if (isset($params['data'])) {
            $form->addData($params['data']);
        }

        return $form;
    }

    private static function normalizeFields($fields)
    {
        $tmp = [];
        foreach ($fields as $k => $v) {
            if (!is_array($v)) {
                $tmp[$v] = [];
            } else {
                $tmp[$k] = $v;
            }
        }

        return $tmp;
    }

    private static function combineParamsFromDB($table, array $order_keys)
    {
        // Fields from DB
        $fields = $types = SQL::getFieldsWithAllData($table);

        unset($fields['id']);

        // Sort to match defined order for form
        $sorted = $not_sorted = [];

        foreach ($fields as $k => $v) {
            if (($key = array_search($k, $order_keys)) !== false) {
                $sorted[$key] = $k;
            } else {
                $not_sorted[] = $k;
            }
        }

        ksort($sorted);
        $fields = array_merge($sorted, $not_sorted);

        $params = [];
        foreach ($fields as $v) {
            $field = [];

            $type = $types[$v]['Type'];
            if (strpos($type, 'text') !== false) {
                $field['type'] = 'textarea';
            }
            if (strpos($type, 'enum') !== false) {
                $field['type'] = 'select';
                $field['options'] = SQL::getEnumPairs($table, $types[$v]['Field']);
            }

            $field['name'] = Converter::charsToNormalTitle($v);

            $params[$v] = $field;
        }

        return $params;
    }
}
