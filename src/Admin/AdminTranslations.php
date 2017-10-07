<?php
declare(strict_types=1);

namespace TMCms\Admin;

use TMCms\Config\Settings;
use TMCms\Files\Finder;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class AdminTranslations
 * @package TMCms\Admin
 */
class AdminTranslations
{
    use singletonOnlyInstanceTrait;

    private static $amount_of_compiled_files = 0;
    private static $init_data = [];
    private static $override_language;

    /**
     * @param string $key
     *
     * @return string
     */
    public function getActualValueByKey(string $key): string
    {
        $this->initData(Finder::getInstance()->getPathFolders(Finder::TYPE_TRANSLATIONS));

        return self::$init_data[$key] ?? $key;
    }

    /**
     * @param string $lng
     *
     * @return $this
     */
    public function setOverrideLanguage(string $lng) {
        self::$override_language = $lng;

        return $this;
    }

    /**
     * @param array $paths_to_load
     *
     * @return bool recompiled or not
     */
    private function initData(array $paths_to_load): bool
    {
        if (Settings::get('disable_cms_translations')) {
            return false; // No translations
        }

        // We need to recompile translations only if count of loaded files is changed
        $count_of_files = count($paths_to_load);
        if (self::$amount_of_compiled_files == $count_of_files) {
            return false;
        }

        $data = [];

        $language = self::$override_language ?: Users::getInstance()->getUserLng();

        foreach ($paths_to_load as $file) {
            $file_path = $file . $language . '.php';

            // If file name supplied without root path
            if (stripos($file_path, DIR_BASE) === false) {
                $file_path = DIR_BASE . $file_path;
            }

            // Find and compile file with translations
            if (file_exists($file_path)) {
                $data_from_file = require_once $file_path;

                if (is_array($data_from_file)) {
                    $data += $data_from_file;
                }
            }
        }

        self::$amount_of_compiled_files = $count_of_files;
        self::$init_data += $data;

        return true;
    }
}