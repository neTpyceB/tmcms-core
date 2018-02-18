<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputRange;

\defined('INC') or exit;

/**
 * Class CmsInputRange
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputRange extends InputRange {
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);

        $this->setValue($value);
        $this->addCssClass('form-control');
    }
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '') {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '><output onforminput="value=weight.value"></output>' . $this->getHelperbox();
    }
}
