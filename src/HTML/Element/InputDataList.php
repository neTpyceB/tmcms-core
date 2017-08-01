<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\InputElement;

defined('INC') or exit;

class InputDataList extends InputElement
{
    protected $options = [];
    protected $options_position = 0;

    /**
     * @param              $name
     * @param string|array $value
     * @param string       $id
     */
    public function __construct($name, $value = '', $id = '')
    {
        parent::__construct();

        $this->setTypeAsText();
        $this->setName($name);

        if ($value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $this->setValue($value);
        }
        $this->setValue($value);
        $this->setId($id ?? $name);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance($name, $value = '', $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options [[key, value], [key, value], [key, value]]
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $value
     * @param string $label
     *
     * @return int
     */
    public function addOption($value, $label = ''): int
    {
        $this->options[] = [$value, $label];

        $res = $this->options_position;
        $this->options_position++;

        return $res;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $id = $this->getId();
        $res = ['<input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . ' list="' . $id . '_datalist">'];
        $res[] = '<datalist id="' . $id . '_datalist">';

        foreach ($this->options as $k => $v) {
            $res[] = ' <option label="' . htmlspecialchars((string)$k, ENT_QUOTES) . '" value="' . htmlspecialchars($v, ENT_QUOTES) . '"></option>';
        }
        $res[] = '</datalist>';

        return implode('', $res);
    }
}