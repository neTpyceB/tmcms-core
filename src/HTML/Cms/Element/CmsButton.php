<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\Button;

defined('INC') or exit;

/**
 * Class CmsButton
 */
class CmsButton extends Button
{
    /**
     *
     * @param string $value
     * @param string $type button type (submit/button)
     *
     * @return $this
     */
    public static function getInstance($value, $type = 'submit')
    {
        return new self($value, $type);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Translate value
        $this->setValue(__($this->getValue()));

        return '<button ' . $this->getAttributesString() . ' class="btn green">' . $this->getValue() . '</button>';
    }
}