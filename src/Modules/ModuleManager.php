<?php

namespace TMCms\Modules;

use TMCms\Admin\Menu;

defined('INC') or exit;

/**
 * Class ModuleManager
 */
class ModuleManager
{
    /**
     * Search for exclusive module CMS pages created in Project folder for this individual site
     *
     * @param string $module
     */
    public static function requireModule($module)
    {
        // Check for module itself
        $file_path = DIR_MODULES . $module . '/' . 'Module' . ucfirst($module) . '.php';
        if (file_exists($file_path)) {
            require_once $file_path;

            // Inner panel file
            if (MODE == 'cms') {
                $file_path = DIR_MODULES . $module . '/' . 'Cms' . ucfirst($module) . '.php';
                if (file_exists($file_path)) {
                    Menu::getInstance()->setMayAddItemsFlag(false);
                    require_once $file_path;
                }
            }
        }

        // Require all Entity files
        $objects_path = DIR_MODULES . $module . '/Entity/';
        if (file_exists($objects_path)) {
            foreach (array_diff(scandir($objects_path), ['.', '..']) as $object_file) {
                if (is_file($objects_path . $object_file)) {
                    require_once $objects_path . $object_file;
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getListOfCustomModuleNames()
    {
        return array_diff(scandir(DIR_MODULES), ['.', '..']);
    }
}