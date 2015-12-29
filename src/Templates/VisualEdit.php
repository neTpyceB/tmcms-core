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

    public function init()
    {
        if ((isset($_GET['cms_visual_edit']) || isset($_SESSION['visual_edit'])) && Users::getInstance()->isLogged() && Settings::get('enable_visual_edit')) {
            self::$enabled = true;

            $_SESSION['visual_edit'] = true;
        }
    }

    /**
     * @param string $controller
     * @param string $key
     * @param string $data
     * @return string
     */
    public function wrapAroundComponents($controller, $key, $data)
    {
        ob_start();

        ?>
        <cms_tag id="<?= $controller . '_' . $key ?>"
                 style="position: relative" class="cms_visual_editable"
                 onclick="cms_visual_edit.edit(this)"
                 data-page_id="<?= PAGE_ID ?>"
                 data-component="<?= $this->_make_component_field($controller, $key); ?>">
        <?= $data ?>
        </cms_tag><?php

        return ob_get_clean();
    }

    /**
     * @param string $controller
     * @param string $key
     * @return string
     */
    private function _make_component_field($controller, $key)
    {
        return str_replace('controller', '', strtolower($controller)) . '_' . $key;
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }
}