<?php
namespace TMCms\HTML\Cms\Element;

use TMCms\Templates\PageHead;

defined('INC') or exit;

class CmsInputTags extends CmsInputText {
    /**
     * @param string $name
     * @param null $value
     * @param null $id
     */
    public function  __construct($name, $value = NULL, $id = NULL) {
        // Add assets
        PageHead::getInstance()
            ->addCssUrl('plugins/bootstrap-tagsinput/bootstrap-tagsinput.css')
            ->addJsUrl('plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js')
        ;

        // Construct general input
        parent::__construct($name, $value, $id);

        // Set required data
        if ($value) {
            $this->setValue($value);
        }

        $this->addCssClass('form-control');

        $this->setAttribute('type', 'text');

        $this->setAttribute('data-role', 'tagsinput');
    }
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     * @return $this
     */
    public static function getInstance($name, $value = null, $id = null) {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString() {
        return '<input '. ($this->isFieldRequired() ? ' required' : '') . $this->getCommonElementValidationAttributes() .' '. $this->getAttributesString() .'>' . $this->getHelperBox();
    }
}