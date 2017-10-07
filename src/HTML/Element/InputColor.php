<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

class InputColor extends Element
{
    /**
     * @param string       $name
     * @param string|array $value
     * @param string       $id
     */
    public function __construct(string $name, $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('color');
        $this->setName($name);

        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $this->setValue($value);
        $this->setId($id ? $id : $name);
    }

    /**
     * @param string $name
     * @param string|array $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, $value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @param string|array $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $this->setAttribute('value', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->getAttribute('value');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '<input ' . $this->getAttributesString() . '>';
    }
}