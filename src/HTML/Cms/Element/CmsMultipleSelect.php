<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\MultipleSelect;

/**
 * Class CmsMultipleSelect
 */
class CmsMultipleSelect extends MultipleSelect {
    /**
     * @param string $name
     * @param string $id
     */
    public function  __construct(string $name, string $id = '') {
        parent::__construct($name, $id);

        $this->addCssClass('form-control');
    }

    /**
     * @param string $name
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $id = '') {
        return new self($name, $id);
    }
}
