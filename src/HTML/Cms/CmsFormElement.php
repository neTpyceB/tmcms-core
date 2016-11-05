<?php
namespace TMCms\HTML\Cms;

use TMCms\HTML\Element;
use TMCms\HTML\FormElement;

defined('INC') or exit;

/**
 * Class CmsFormElement
 */
class CmsFormElement extends FormElement
{
    protected $rowAttributes = array();

    /**
     * @param string $label
     * @param Element $element
     * @param string $rowAttributes
     */
    public function  __construct($label, Element $element, $rowAttributes = '')
    {
        parent::__construct($label, $element);
        if (is_string($rowAttributes)) $this->rowAttributes = array($rowAttributes);
        elseif (is_array($rowAttributes)) $this->rowAttributes = $rowAttributes;
        else dump('Incorrect row attributes');
    }

    /**
     * @return array
     */
    public function getRowAttributes()
    {
        return $this->rowAttributes;
    }

    /**
     * @return string
     */
    public function getRowAttributesString()
    {
        return implode(' ', $this->rowAttributes);
    }

    /**
     * Get title's vertical alignment
     * @return string
     */
    public function getlabelValign()
    {
        return $this->element->labelValign();
    }
}