<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\CheckboxList;

defined('INC') or exit;

class CmsCheckboxList extends CheckboxList
{
    /**
     * @param string $name
     * @param string $id
     */
    public function __construct(string $name, string $id = '')
    {
        parent::__construct($name, $id);
    }

    /**
     *
     * @param string $name
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $id = '')
    {
        return new self($name, $id);
    }

    /**
     * @param array $checkboxes
     *
     * @return $this
     */
    public function setCheckboxes(array $checkboxes)
    {
        foreach ($checkboxes as $name => $label) {
            $this->checkboxes[$label] = new CmsCheckbox($name);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstElementId()
    {
        foreach ($this->checkboxes as $cb) {
            /** @var CmsCheckbox $cb */
            return $cb->getId();
        }

        return '';
    }
}