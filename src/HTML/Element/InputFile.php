<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputFile
 * @package TMCms\HTML\Element
 */
class InputFile extends Element
{
    protected $is_multiple = false;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('file');
        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ? $name : $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->is_multiple) {
            $this->setName($this->getName() . '[]');
        }

        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . ($this->is_multiple ? ' multiple' : '') . '>';
    }

    /**
     * @param bool $multiple
     *
     * @return $this
     */
    public function setIsMultiple(bool $multiple)
    {
        $this->is_multiple = $multiple;

        return $this;
    }
}
