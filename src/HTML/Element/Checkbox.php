<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

class Checkbox extends Element
{
    protected $checked = false;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('checkbox');
        $this->setName($name);
        $this->setValue($value);

        $this->setId($id ? $id : $name);
    }

    /**
     * @return $this
     */
    public function setIsChecked()
    {
        $this->setChecked(true);

        return $this;
    }

    /**
     * @param bool $checked
     *
     * @return $this
     */
    public function setChecked(bool $checked)
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        ?>
        <input <?= $this->getCommonElementValidationAttributes() . $this->getAttributesString() ?><?= $this->isChecked() ? ' checked="checked"' : '' ?>>&nbsp;
        <?php

        return ob_get_clean();
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }
}