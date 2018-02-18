<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputRadio;

\defined('INC') or exit;

/**
 * Class CmsInputRadio
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputRadio extends InputRadio {

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value, string $id = '') {
        parent::__construct($name, $value);

        $this->setId($id ? $name : $id);
        $this->addCssClass('noBorder');
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value) {
        return new self($name, $value);
    }
}
