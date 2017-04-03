<?php

namespace TMCms\Admin;

use TMCms\Config\Settings;
use TMCms\Files\Finder;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined ('INC') or exit;

class AdminTranslations {
    use singletonOnlyInstanceTrait;

    private static $amount_of_complied_files = 0;
    private static $init_data = [];

	public function getActualValueByKey($key) {
        $this->init_data(Finder::getInstance()->getPathFolders(Finder::TYPE_TRANSLATIONS));

		return is_string($key) && isset(self::$init_data[$key]) ? self::$init_data[$key] : $key;
	}

    private function init_data($paths_to_load)
	{
        if (Settings::get('disable_cms_translations')) {
			return; // No translations
		}

        // We need to recompile translations only if count of loaded files is changed
        $so = count($paths_to_load);
        if (self::$amount_of_complied_files == $so) {
            return;
        }

		$data = [];
        foreach ($paths_to_load as $file) {
			$file_path = $file . Users::getInstance()->getUserLng() . '.php';
			if (stripos($file_path, DIR_BASE) === false) {
				$file_path = DIR_BASE . $file_path;
			}
			if (file_exists($file_path)) {
                $data_from_file = require_once $file_path;
                if (is_array($data_from_file)) {
                    $data += $data_from_file;
                }
			}
		}

        self::$amount_of_complied_files = $so;
        self::$init_data += $data;
	}
}