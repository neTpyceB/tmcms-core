<?php
declare(strict_types=1);

namespace TMCms\Templates;

use TMCms\Files\FileSystem;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsCheckboxList;
use TMCms\HTML\Cms\Element\CmsInputColor;
use TMCms\HTML\Cms\Element\CmsInputDate;
use TMCms\HTML\Cms\Element\CmsInputTags;
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsMultipleSelect;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Element\CmsTextarea;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\HTML\Cms\Widget\SitemapPages;
use TMCms\HTML\Element;
use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class ComponentsHelper
 */
class RenderComponentHelper
{
    use singletonInstanceTrait;

    private $component_name = '';
    private $field_params = [];
    private $field_type = 'text';
    private $widget_type = '';
    private $data;
    private $page_id = 0;
    private $selected = '';

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setComponentName(string $name): self
    {
        $this->component_name = $name;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setFieldData($value): self
    {
        $this->field_params = $value;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setFieldType(string $type): self
    {
        $this->field_type = $type;

        return $this;
    }

    /**
     * @param string $edit
     *
     * @return $this
     */
    public function setWidgetType(string $edit): self
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

            case 'date':

                $field = CmsInputDate::getInstance($this->component_name);

                break;

            case 'textarea':

                $field = CmsTextarea::getInstance($this->component_name);

                // Height
                if (isset($this->field_params['rows'])) {
                    $field->setRowCount($this->field_params['rows']);
                }

                break;

            case 'checkbox':

                $field = CmsCheckbox::getInstance($this->component_name);

                // Checked
                if (!isset($this->field_params['checked']) && isset($this->data[$this->component_name])) {
                    $this->field_params['checked'] = $this->data[$this->component_name];
                }

                if (isset($this->field_params['checked'])) {
                    $field->setChecked((bool)$this->field_params['checked']);
                }

                break;

            case 'checkbox_list':

                $field = CmsCheckboxList::getInstance($this->component_name);

                if (isset($this->field_params['checkboxes'])) {
                    $field->setCheckboxes($this->field_params['checkboxes']);
                }

                if (!isset($this->field_params['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_params['selected'] = unserialize($this->data[$this->component_name], true);
                }

                if (isset($this->field_params['selected'])) {
                    $field->setChecked(array_keys($this->field_params['selected']));
                }

                $field->setListView(true);

                break;

            case 'options':
            case 'select':

                $field = CmsSelect::getInstance($this->component_name);

                // Options
                if (isset($this->field_params['options'])) {
                    $field->setOptions($this->field_params['options']);
                }

                // Selected options
                if (!isset($this->field_params['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_params['selected'] = $this->data[$this->component_name];
                }

                if (isset($this->field_params['selected'])) {
                    $field->setSelected($this->field_params['selected']);
                    $this->selected = $this->field_params['selected'];
                } else {
                    $this->selected = false;
                }

                break;

            case 'multiselect':

                $field = CmsMultipleSelect::getInstance($this->component_name);

                if (isset($this->field_params['options'])) {
                    $field->setOptions($this->field_params['options']);
                }

                if (!isset($this->field_params['selected']) && isset($this->data[$this->component_name])) {
                    $this->field_params['selected'] = $this->data[$this->component_name];
                }

                if (isset($this->field_params['selected'])) {
                    $field->setSelected($this->field_params['selected']);
                    $this->selected = $this->field_params['selected'];
                } else {
                    $this->selected = false;
                }

                $field->disableHelperbox();

                break;

            case 'tags':

                $field = CmsInputTags::getInstance($this->component_name);

                break;

            case 'color':

                $field = CmsInputColor::getInstance($this->component_name);

                break;

            case 'custom':

                // Skip because we have separate page for it

                break;
        }

        // Disable custom css styles for select elements
        if ($field && isset($this->field_params['disable_custom_plugin'])) {
            $field->disableCustomStyled();
        }

        // Required for Widgets
        if ($field) {
            $field->setAttribute('data-page_id', $this->page_id);
        }

        // Set Widget
        switch ($this->widget_type) {
            default:
            case '':

                break;

            case 'wysiwyg':
            case 'tinymce':

                $field->enableWysiwyg();

                if (!empty($this->field_params['wysiwyg_options'])) {
                    $field->getWidget()->wysiwyg_options = $this->field_params['wysiwyg_options'];
                }

                break;

            case 'files':

                $widget = new FileManager;

                // Path to opened folder
                if (isset($this->field_params['path'])) {
                    if (!file_exists(DIR_BASE . $this->field_params['path'])) {
                        FileSystem::mkDir(DIR_BASE . $this->field_params['path']);
                    }

                    $widget->setPath($this->field_params['path']);
                }

                // Allowed extensions
                if (isset($this->field_params['allowed_extensions'])) {
                    $widget->setAllowedExtensions($this->field_params['allowed_extensions']);
                }

                $field->setWidget($widget);

                break;

            case 'pages':

                $widget = new SitemapPages;

                if (isset($this->field_params['lng'])) {
                    $widget->setLanguage($this->field_params['lng']);
                }

                $field->setWidget($widget);

                break;

            case 'map':

                $field->enableGoogleMap();

                break;

            case 'color':

                $field->enableMiniColors();

                break;
        }

        // Special features for field
        if (isset($this->field_params['hint'])) {
            $field->setHintText($this->field_params['hint']);
        }

        return $field;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setPageId(int $id): self
    {
        $this->page_id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function selectedOption(): string
    {
        return (string)$this->selected;
    }
}
