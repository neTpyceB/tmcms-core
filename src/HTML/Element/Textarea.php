<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

class Textarea extends Element
{
    /**
     * @param string $name
     * @param string $value
     *
     * @param string $id
     */
    public function __construct(string $name, string $value = '', $id = '')
    {
        parent::__construct();

        $this->setName($name);

        if ($value) {
            $this->setValue($value);
        }

        $this->setId($id ?: $name);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '<textarea ' . $this->getAttributesString(['value']) . '>' . $this->getValue() . '</textarea>';
    }
}