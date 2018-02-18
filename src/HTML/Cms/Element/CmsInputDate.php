<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Cms\HelperBox;
use TMCms\HTML\Element\InputDate;

\defined('INC') or exit;

/**
 * Class CmsInputDate
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputDate extends InputDate {
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);

        $this->setValue($value);
        $this->addCssClass('form-control');
        $this->enableDateTimePicker();
    }
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     * @return CmsInputDate
     */
    public static function getInstance(string $name, string $value = '', string $id = '') {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->isEnabledCalendarDatepicker()) {
            $this->addCssClass('datepicker');
        } elseif ($this->isEnabledDateTimePicker()) {
            $this->addCssClass('datetimepicker');
        }

        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '>' . $this->getHelperbox();
    }
}
