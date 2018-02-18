<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Cms\HelperBox;
use TMCms\HTML\Element\InputUrl;

\defined('INC') or exit;

/**
 * Class CmsInputUrl
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputUrl extends InputUrl
{
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);

        $this->setValue($value);
        $this->addCssClass('form-control');
        $this->validateUrl();
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
        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '>' . $this->getHelperbox();
    }
}
