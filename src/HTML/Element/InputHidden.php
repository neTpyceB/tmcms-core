<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputHidden
 * @package TMCms\HTML\Element
 */
class InputHidden extends Element
{
    protected $invisible = true;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('hidden');
        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ?: $name);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        ?><input <?= $this->getCommonElementValidationAttributes() . $this->getAttributesString() ?>><?php

        return ob_get_clean();
    }
}
