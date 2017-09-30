<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

class Button extends Element
{

    /**
     * @param string $value
     * @param string $type
     */
    public function __construct(string $value, string $type = 'submit') {
        $this->setType($type);
        $this->setValue($value);
	}

    /**
     * @return string
     */
    public function __toString() {
		return '<input btn-parsley '. $this->getAttributesString() .'>';
	}
}