<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputColor;

defined('INC') or exit;

class CmsInputColor extends InputColor {
	protected $backup = true;
	protected $hint_format = '';
	protected $helper = true;
	protected $value_array = [];

    /**
     * @param string $name
     * @param string|array $value
     * @param string  $id
     */
    public function  __construct(string $name, $value = '', string $id = '') {
		parent::__construct($name, $value, $id);

		if ($value && is_array($value)) {
			$this->value_array = $value;
		}

        $this->addCssClass('form-control');
	}
	/**
	 * @param string $name
	 * @param string|array $value
	 * @param string $id
	 * @return CmsInputColor
	 */
	public static function getInstance(string $name, $value = '', string $id = '') {
		return new self($name, $value, $id);
	}

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '>' . $this->getHelperbox();
    }
}