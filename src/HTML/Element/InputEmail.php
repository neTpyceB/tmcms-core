<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputEmail
 * @package TMCms\HTML\Element
 */
class InputEmail extends Element
{
    /**
     * @param string $name
     * @param string|array $value
     * @param string $id
     */
    public function __construct(string $name, $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('email');
        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ?: $name);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '<input ' . $this->getAttributesString() . '>';
    }
}
