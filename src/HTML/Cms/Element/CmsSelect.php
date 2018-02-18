<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\Select;

\defined('INC') or exit;

/**
 * Class CmsSelect
 * @package TMCms\HTML\Cms\Element
 */
class CmsSelect extends Select {
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);
        $this->addCssClass('autoSelect');
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '') {
        return new self($name, $value, $id);
    }
}
