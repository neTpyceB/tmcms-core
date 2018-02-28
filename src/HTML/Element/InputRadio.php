<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputRadio
 * @package TMCms\HTML\Element
 */
class InputRadio extends Element
{
    /**
     * @var bool
     */
    protected $selected;
    protected $parent_radio_box;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('radio');
        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ?: $name);
    }

    /**
     * @param bool
     *
     * @return $this
     */
    public function setSelected(bool $selected)
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * @return RadioBox
     */
    public function getParentRadioBox(): RadioBox
    {
        return $this->parent_radio_box;
    }

    /**
     * @param RadioBox $radio_box
     *
     * @return $this
     */
    public function setParentRadioBox(RadioBox $radio_box)
    {
        $this->parent_radio_box = $radio_box;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        ?><input style="padding: 0; vertical-align: bottom; position: relative; top: -1px; *overflow: hidden; margin: 0 2px 0 0;"
            <?= $this->getCommonElementValidationAttributes() . $this->getAttributesString() ?><?= $this->isSelected() ? ' checked="checked"' : '' ?>>
        <?php

        return ob_get_clean();
    }

    /**
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }
}
