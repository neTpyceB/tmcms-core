<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputHidden;

\defined('INC') or exit;

/**
 * Class CmsInputHidden
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputHidden extends InputHidden {
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
