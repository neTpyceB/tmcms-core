<?php
declare(strict_types=1);

namespace TMCms\HTML;

defined('INC') or exit;

class FormElement
{
    protected $label = '';

    /* @var $element Element */
    protected $element;

    /**
     * @param string $label
     * @param Element $element
     */
    public function __construct($label, Element $element)
    {
        $this->label = $label;
        $this->element = $element;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Element
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param Element $element
     *
     * @return $this
     */
    public function setElement(Element $element)
    {
        $this->element = $element;

        return $this;
    }
}