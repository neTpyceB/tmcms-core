<?php

namespace TMCms\Admin;

use TMCms\Config\Settings;
use TMCms\Files\Finder;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined ('INC') or exit;

class AdminTranslations {
    use singletonOnlyInstanceTrait;

	private static $init_data = [];

	public function getActualValueByKey($key) {
		if (!self::$init_data) {
			$this->init_data();
		}

		return is_string($key) && isset(self::$init_data[$key]) ? self::$init_data[$key] : $key;
	}

	private function init_data()
	{
		if (Settings::getInstance()->get('disable_cms_translations')) {
			return; // No translations
		}

		$data = [];
		foreach (Finder::getInstance()->getPathFolders(Finder::TYPE_TRANSLATIONS) as $file) {
			$file_path = $file . Users::getInstance()->getUserLng() . '.php';
			if (stripos($file_path, DIR_BASE) === false) {
				$file_path = DIR_BASE . $file_path;
			}
			if (file_exists($file_path)) {
				$data += require_once $file_path;
			}
		}

		self::$init_data = $data;
	}
}