<?php

namespace TMCms\Templates;

use TMCms\Files\FileSystem;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsCheckboxList;
use TMCms\HTML\Cms\Element\CmsInputTags;
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsMultipleSelect;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Element\CmsTextarea;
use TMCms\HTML\Cms\Widget\Calendar;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\HTML\Cms\Widget\SitemapPages;
use TMCms\HTML\Cms\Widget\Tinymce;
use TMCms\HTML\Element;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class ComponentsHelper
 */
class RenderComponentHelper {
    use singletonInstanceTrait;

    private $component_name = '';
    private $field_value = '';
    private $field_type = 'text';
    private $widget_type = '';
    private $data;
    private $page_id = 0;
    private $selected = false;

    /**
     * @param string $name
     * @return $this
     */
    public function setComponentName($name) {
        $this->component_name = $name;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setFieldData($value)
    {
        $this->field_value = $value;

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setFieldType($type)
    {
        $this->field_type = $type;

        return $this;
    }

    /**
     * @param string $edit
     * @return $this
     */
    public function setWidgetType($edit)
    {
        $this->widget_type = $edit;

        return $this;
    }

    /**
     * @return Element
     */
    public function getFieldView()
    {
        $field = NULL;

        // Type
        switch ($this->field_type) {
            default:
            case 'text':
                $field = CmsInputText::getInstance($this->component_name);
                break;

            case 'textarea':
                $field = CmsTextarea::getInstance($this->component_name);

                // Height
                if (isset($this->field_value['rows'])) {
                    $field->setRowCount($this->field_value['rows']);
                }
                break;

            case 'checkbox':
                $field = CmsCheckbox::getInstance($this->component_name);

                // Checked
                if (!isset($this->field_value['checked']) && isset($this->data[$this->component_name])) {
                    $this->field_value['checked'] = $this->data[$this->component_name];
                }
                if (isset($this->field_value['checked'])) {
                    $field->setChecked($this->field_value['checked']);
                }
                break;

            case 'checkbox_list':
                $field = CmsCheckboxList::getInstance($this->component_name);

                if (isset($this->field_value['checkboxes'])) {
                    $field->setCheckboxes($this->field_value['checkboxes']);
                }

                if (!isset($this->field_value['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_value['selected'] = unserialize($this->data[$this->component_name]);
                }

                if (isset($this->field_value['selected'])) {
                    $field->setChecked(array_keys($this->field_value['selected']));
                }

                $field->setListView(true);
                break;

            case 'options':
            case 'select':
                $field = CmsSelect::getInstance($this->component_name);

                // Options
                if (isset($this->field_value['options'])) {
                    $field->setOptions($this->field_value['options']);
                }
                // Selected options
                if (!isset($this->field_value['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_value['selected'] = $this->data[$this->component_name];
                }
                if (isset($this->field_value['selected'])) {
                    $field->setSelected($this->field_value['selected']);
                    $this->selected = $this->field_value['selected'];
                } else {
                    $this->selected = false;
                }
                break;

            case 'multiselect':
                $field = CmsMultipleSelect::getInstance($this->component_name);

                if (isset($this->field_value['options'])) {
                    $field->setOptions($this->field_value['options']);
                }

                if (!isset($this->field_value['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_value['selected'] = $this->data[$this->component_name];
                }

                if (isset($this->field_value['selected'])) {
                    $field->setSelected($this->field_value['selected']);
                    $this->selected = $this->field_value['selected'];
                } else {
                    $this->selected = false;
                }

                $field->helper(false);

                break;

            case 'tags':
                $field = CmsInputTags::getInstance($this->component_name);
                break;

            case 'custom':
                // Skip because we have separate page for it
                break;
        }

        // Required for Widgets
        if ($field) {
            $field->setAttribute('data-page_id', $this->page_id);
        }

        // Set Widget
        switch ($this->widget_type) {
            case '':
                break;

            case 'wysiwyg':
                $field->enableWysiwyg();
                break;

            case 'tinymce':
                $field->enableWysiwyg();
                break;

            case 'calendar':
                $widget = new Calendar;

                if (isset($this->field_value['format'])) {
                    $widget->dateFormat($this->field_value['format']);
                }
                if (isset($this->field_value['showtime'])) {
                    $widget->showTime($this->field_value['showtime']);
                }
                $field->setWidget($widget);
                break;

            case 'files':
                $widget = new FileManager;

                // Path to opened folder
                if (isset($this->field_value['path'])) {
                    if (!file_exists(DIR_BASE . $this->field_value['path'])) FileSystem::mkDir(DIR_BASE . $this->field_value['path']);
                    $widget->path($this->field_value['path']);
                }

                // Allowed extensions
                if (isset($this->field_value['allowed_extensions'])) {
                    $widget->setAllowedExtensions($this->field_value['allowed_extensions']);
                }

                $field->setWidget($widget);
                break;

            case 'pages':
                $field->setWidget(new SitemapPages);
                break;

            case 'map':
                $field->enableGoogleMap();
                break;
        }

        // Special features for field
        if (isset($this->field_value['hint'])) {
            $field->setHintText($this->field_value['hint']);
        }

        return $field;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setPageId($id)
    {
        $this->page_id = (int)$id;

        return $this;
    }

    /**
     * @return bool
     */
    public function selectedOption() {
        return $this->selected;
    }
}