<?php

namespace TMCms\HTML\Cms;

use TMCms\DB\SQL;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsCheckboxList;
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
            $params['db_table'] = $table;
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

        if (isset($params['action'])) {
            $form->setAction($params['action']);
        }

        if (isset($params['title'])) {
            $form->setFormTitle($params['title']);
        }

        if (isset($params['button'])) {
            $form->setSubmitButton($params['button']);
        }

        if (isset($params['collapsed'])) {
            $form->setCollapsed($params['collapsed']);
        }

        if (isset($params['ajax']) && $params['ajax']) {
            $form->enableAjax();
        }

        if (isset($params['cancel']) && $params['cancel']) {
            if (is_bool($params['cancel'])) {
                $params['cancel'] = __('Cancel');
            }
            $form->setCancelButton($params['cancel']);
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
                    $name = Converter::symb2Ttl($key);
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
                        $cms_field->setFormat($field['format']);
                    }
                } elseif ($field['type'] == 'password') {
                    $cms_field = CmsInputPassword::getInstance($key);
                } elseif ($field['type'] == 'row') {
                    if (isset($field['value'])) {
                        $cms_field = CmsRow::getInstance($key)->value($field['value']);
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
                    $cms_field = CmsInputFile::getInstance($key);
                } elseif ($field['type'] == 'color') {
                    $cms_field = CmsInputColor::getInstance($key);
                }

                if ($cms_field) {
                    // Extra params
                    if (isset($field['options']) && is_array($field['options'])) {
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
                        $cms_field->value($field['value']);
                    }
                    if (isset($field['selected'])) {
                        $cms_field->setSelected($field['selected']);
                    }
                    if (isset($field['multiple'])) {
                        $cms_field->multiple(true);
                    }
                    if (isset($field['multilng'])) {
                        $cms_field->enableMultiLng();
                    }
                    if (isset($field['translation'])) {
                        $cms_field->enableMultiLng();
                    }
                    if (isset($field['required'])) {
                        $cms_field->validateRequired();
                    }
                    if (isset($field['uid'])){
                        $cms_field->setUid($field['uid']);
                    }


                    // Disable custom css styles for select elements
                    if (isset($field['disable_custom_plugin'])) {
                        $cms_field->disableCustomStyled();
                    }

                    // Autogenerated slug
                    if (isset($field['uid'])) {
                        $to = $field['uid'];
                        // Check if we need to change key for current language translation
                        if (isset($params['fields'][$field['uid']]['translation']) && $params['fields'][$field['uid']]['translation']) {
                            $to = $to . '_'. LNG .'_';
                        }

                        $cms_field->enableSlugGenerationUidFromField($key, $to);
                    }
                    if (isset($field['readonly']) && $field['readonly']) {
                        $cms_field->readonly(true);
                    }
                    if (isset($field['html'])) {
                        $cms_field->html($field['html']);
                    }
                    if (isset($field['rows'])) {
                        $cms_field->rows($field['rows']);
                    }
                    if (isset($field['backup'])) {
                        $cms_field->backup($field['backup']);
                    }
                    if (isset($field['help'])) {
                        $cms_field->help($field['help']);
                    }
                    if (isset($field['hint'])) {
                        $cms_field->hint($field['hint']);
                    }
                    if (isset($field['min'])) {
                        $cms_field->min($field['min']);
                    }
                    if (isset($field['max'])) {
                        $cms_field->max($field['max']);
                    }
                    if (isset($field['step'])) {
                        $cms_field->step($field['step']);
                    }
                    if (isset($field['maxlength'])) {
                        $cms_field->maxlength($field['maxlength']);
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
                                break;

                            // Google map for choosing coordinates
                            case 'map':
                                $cms_field->enableGoogleMap();
                                break;

                            // Structure pages
                            case 'pages':
                                $cms_field->setWidget(new SitemapPages());
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
                                    $file_manager->path($field['path']);
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
                        if (isset($field['validate']['required']) || in_array('required', $field['validate'])) {
                            $cms_field->validateRequired();
                        }
                        if (isset($field['validate']['is_digit']) || in_array('is_digit', $field['validate'])) {
                            $cms_field->validateDigits();
                        }
                        if (isset($field['validate']['number']) || in_array('number', $field['validate'])) {
                            $cms_field->validateNumber();
                        }
                        if (isset($field['validate']['alphanum']) || in_array('alphanum', $field['validate'])) {
                            $cms_field->validateAlphaNumeric();
                        }
                        if (isset($field['validate']['url']) || in_array('url', $field['validate'])) {
                            $cms_field->validateUrl();
                        }
                        if (isset($field['validate']['email']) || in_array('email', $field['validate'])) {
                            $cms_field->validateEmail();
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

            $field['name'] = Converter::symb2Ttl($v);

            $params[$v] = $field;
        }

        return $params;
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
}