<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Cms\HelperBox;
use TMCms\HTML\Element\InputEmail;

defined('INC') or exit;

/**
 * Class CmsInputEmail
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputEmail extends InputEmail
{
    protected $backup = true;
    protected $hint_format = '';
    protected $helper = true;
    protected $value_array = [];

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct($name, $value, $id);

        if (is_array($value)) {
            $this->value_array = $value;
        }

        $this->addCssClass('form-control');
        $this->validateEmail();
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return CmsInputEmail
     */
    public static function getInstance(string $name, string $value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $helper = $this->helper
            ? new HelperBox($this->getId(), $this->getMaxlength(), $this->getBackup() && !$this->isDisabled(), $this->hint_format, $this->getValue())
            : '';

        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '>' . $helper;
    }
}