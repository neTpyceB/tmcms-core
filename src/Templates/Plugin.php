<?php

namespace TMCms\Templates;

use TMCms\Admin\Structure\Entity\PageComponentEntityRepository;
use TMCms\Config\Settings;
use TMCms\Files\FileSystem;
use TMCms\Files\Finder;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class Plugin
 */
class Plugin
{
    use singletonInstanceTrait;

    /**
     * Saved data from DB for fast access
     * @var array
     */
    private static $selected_plugin = '';
    private static $data_initialized = []; // Data for plugin fields
    private static $data = [];

    private $plugin_files = [];

    /**
     * Get all components (fields) used in object
     * @return array
     */
    public static function getComponents()
    {
        return [];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function getSelectedPluginValue($name)
    {
        self::init();

        $name .= '_select_plugin';

        $res = isset(self::$data[$name]) ? self::$data[$name] : '';

        self::$selected_plugin = $name;

        return $res;
    }

    /**
     * Render view, gets HTML to put in browser
     * N.B. Implement this method to render your own required content
     */
    public function render()
    {
        ?>NO CONTENT<?php
    }

    /**
     * Gets unresolved data, alias of getValue($key)
     * @param $key
     * @return string
     */
    public function __get($key)
    {
        $res = $this->getValue($key);

        return $res;
    }

    /**
     * @param $plugin_field_name
     * @return string
     */
    public function getValue($plugin_field_name)
    {
        self::init();

        $field_full_name = self::$selected_plugin .'_'. $plugin_field_name;

        if (isset(self::$data[$field_full_name])) {
            return self::$data[$field_full_name];
        }

        return ''; // No component - empty content
    }

    /**
     * Preload all data of plugins
     */
    private static function init()
    {
        if (!self::$data_initialized) {
            self::$data_initialized = true;

            $page_components_collection = new PageComponentEntityRepository();
            $page_components_collection->setWherePageId(PAGE_ID);
            $page_components_collection->addWhereFieldIsLike('component', 'select_plugin');
            if (Settings::isCacheEnabled()) {
                $page_components_collection->enableUsingCache();
            }

            self::$data = $page_components_collection->getPairs('data', 'component');
        }
    }

    /**
     * @return array
     */
    public function getPluginFilePairs() {
        if ($this->plugin_files) { // Already have found filed
            return $this->plugin_files;
        }

        foreach (Finder::getInstance()->getPathFolders(Finder::TYPE_PLUGINS) as $folder) {
            $folder = DIR_BASE . $folder;
            FileSystem::mkDir($folder);

            // Skip folder links
            $cms_plugin_files = array_diff(scandir($folder), ['.', '..']);

            // Make simple names for rendering in selects
            foreach ($cms_plugin_files as $k => $v) {
                $this->plugin_files[$v] = str_replace('plugin.php', '', $v);
            }
        }

        return $this->plugin_files;
    }
}