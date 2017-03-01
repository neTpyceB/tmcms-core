<?php

namespace TMCms\Templates;

use TMCms\Admin\Users;
use TMCms\Config\Settings;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class VisualEdit
 * Used to show inline editor on site. Called in Frontend App
 */
class VisualEdit
{
    use singletonInstanceTrait;

    private static $enabled = false;

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    public function init()
    {
        if (Users::getInstance()->isLogged() && Settings::get('enable_visual_edit')) {
            self::$enabled = true;
        }
    }

    /**
     * @param string $controller
     * @param string $key
     * @param string $data
     * @param string $type
     * @return string
     */
    public function wrapAroundComponents($controller, $key, $data, $type = 'component')
    {
        // Only editable strings
        if (!is_scalar($data)) {
            return $data;
        }

        ob_start();

        ?>
        <cms_tag id="<?= $controller . '_' . $key ?>"
                 style="position: relative" class="cms_visual_editable"
                 onclick="cms_visual_edit.edit(this)"
                 data-page_id="<?= PAGE_ID ?>"
                 data-type="<?= $type ?>"
                 data-component="<?= $this->_make_component_field($controller, $key); ?>"><?= trim($data) ?></cms_tag><?php

        return ob_get_clean();
    }

    /**
     * @param string $controller
     * @param string $key
     * @return string
     */
    private function _make_component_field($controller, $key)
    {
        if (!$controller) {
            return $key;
        }

        return str_replace('controller', '', strtolower($controller)) . '_' . $key;
    }
}