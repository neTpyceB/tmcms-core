<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputText
 * @package TMCms\HTML\Element
 */
class InputText extends Element
{
    private $plugin_xeditable = false;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('text');
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
    public static function getInstance(string $name, string$value = '', string $id = '')
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

    /**
     * @return $this
     */
    public function enableXEditable() {
        $this->plugin_xeditable = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledXEditable(): bool
    {
        return $this->plugin_xeditable;
    }
}
