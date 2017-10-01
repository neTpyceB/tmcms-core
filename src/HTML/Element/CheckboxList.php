<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

class CheckboxList extends Element
{
    protected $checkboxes = [];
    protected $checked = [];
    protected $list_view = false;

    /**
     * @param string $name
     * @param string $id
     */
    public function __construct(string $name, string $id = '')
    {
        parent::__construct();

        $this->setName($name);
        $this->setId($id ? $id : $name);
    }

    /**
     * @return array
     */
    public function getCheckboxes(): array
    {
        return $this->checkboxes;
    }

    /**
     * @param array $checkboxes
     *
     * @return $this
     */
    public function setCheckboxes(array $checkboxes)
    {
        foreach ($checkboxes as $name => $label) {
            $this->checkboxes[$label] = new Checkbox($name);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getChecked(): array
    {
        $res = [];

        foreach ($this->checkboxes as $checkbox) {
            /* @var $checkbox Checkbox */
            if ($checkbox->isChecked()) {
                $res[] = $checkbox->getName();
            }
        }

        return $res;
    }

    /**
     * @param array $checked
     *
     * @return $this
     */
    public function setChecked(array $checked)
    {
        foreach ($this->checkboxes as $checkbox) {
            /* @var $checkbox Checkbox */
            $checkbox->setChecked(in_array($checkbox->getName(), $checked));
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function toArray(): array
    {
        $res = [];

        foreach ($this->checkboxes as $label => $checkbox) {
            /* @var $checkbox Checkbox */
            $res[(string)$checkbox->getName()] = $label;
        }

        return $res;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        if ($this->isListView()) {
            echo '<table>';
        }

        foreach ($this->checkboxes as $label => $checkbox) {
            /* @var $checkbox Checkbox */
            $label = htmlspecialchars($label);

            $checkbox_clone = clone $checkbox;
            $checkbox_clone->setName($this->getName() . '[' . $checkbox_clone->getName() . ']');

            if ($this->isListView()) {
                echo '<tr><td>' . $checkbox_clone . '</td><td><label for="' . $checkbox_clone->getId() . '">' . $label . '</label></td>';
            } else {
                echo '<label style="margin-right:13px">' . $checkbox_clone . $label . '</label>';
            }
        }

        if ($this->isListView()) {
            echo '</table>';
        }

        return ob_get_clean();
    }

    /**
     * @return bool
     */
    public function isListView()
    {
        return $this->list_view === true;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setListView($flag)
    {
        $this->list_view = (bool)$flag;

        return $this;
    }
}