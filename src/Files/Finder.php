<?php

namespace TMCms\Files;

use TMCms\Config\Settings;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class Finder used to register custom folder for file loading
 * @package TMCms\Files
 */
class Finder {
	use singletonInstanceTrait;

	const TYPE_ASSETS = 'assets';
	const TYPE_AJAX = 'ajax';
	const TYPE_PLUGINS = 'plugins';
	const TYPE_SERVICES = 'services';
	const TYPE_TRANSLATIONS = 'translations';

	private $assets_search_folders = [DIR_CMS_SCRIPTS_URL];
	private $ajax_search_folders = [DIR_FRONT_AJAX_URL];
	private $plugin_search_folders = [DIR_FRONT_PLUGINS_URL];
	private $services_search_folders = [DIR_FRONT_SERVICES_URL];
	private $translations_search_folders = [];

	public function addAssetsSearchPath($path)
	{
		$this->assets_search_folders[] = $path;

        return $this;
	}

	public function addAjaxSearchPath($path)
	{
		$this->ajax_search_folders[] = $path;

        return $this;
	}

	public function addPluginsSearchPath($path)
	{
		$this->plugin_search_folders[] = $path;

        return $this;
	}

	public function addServicesSearchPath($path)
	{
		$this->services_search_folders[] = $path;

        return $this;
	}

	public function addTranslationsSearchPath($path)
	{
		$this->translations_search_folders[] = $path;

        return $this;
	}

	public function searchForRealPath($real_file_path, $type = self::TYPE_ASSETS) {
		$search_array = $this->getPathFolders($type);
		$found_path = false;
		$external = false;

		// External path?
		if (($url = @parse_url($real_file_path)) && isset($url['host']) && $url['host'] != CFG_DOMAIN) {
			$found_path = $real_file_path;
			$external = true;
		}

		// Straight path to local file
		if (!$found_path && file_exists(DIR_BASE . $real_file_path)) {
			$found_path = $real_file_path;
		}

		if (!$found_path) {
			foreach ($search_array as $folder) {
				// Search folders with relative path
				if (file_exists(rtrim(DIR_BASE, '/') . $folder . $real_file_path)) {
					$found_path = rtrim(DIR_BASE_URL, '/') . $folder . $real_file_path;
					break;
				}

				// Search folders with basename
				$basename = basename($real_file_path);
				if (file_exists(rtrim(DIR_BASE, '/') . $folder . $basename)) {
					$found_path = rtrim(DIR_BASE_URL, '/') . $folder . $basename;
					break;
				}
			}
		}
/*
		// If file from external composer vendor - should copy to public dir
		if (stripos($found_path, '/vendor/') === 0) {
			$copy_from = DIR_BASE . ltrim($found_path, '/');
			$copy_to = DIR_ASSETS . ltrim($real_file_path, '/');
			if (file_exists($copy_from) && !file_exists($copy_to)) {
				FileSystem::mkDir(pathinfo($copy_to, PATHINFO_DIRNAME));
				copy($copy_from, $copy_to);
			}
			$found_path = DIR_ASSETS_URL . ltrim($real_file_path, '/');
		}
*/
		// Add cache stamp for frontend assets
		if (!$external && $type == self::TYPE_ASSETS) {
			$found_path .= '?' . Settings::get('last_assets_invalidate_time');
		}

		if (!$found_path) {
			dump('File "'. $real_file_path .'" with type "'. $type .'" not found');
		}


		return $found_path;
	}

	/**
	 * @param string $type - choose from Finder constants
	 * @return array
	 */
	public function getPathFolders($type) {
		$search_array = [];
		switch ($type) {
			case self::TYPE_ASSETS:
				$search_array = $this->assets_search_folders;
				break;
			case self::TYPE_AJAX:
				$search_array = $this->ajax_search_folders;
				break;
			case self::TYPE_PLUGINS:
				$search_array = $this->plugin_search_folders;
				break;
			case self::TYPE_SERVICES:
				$search_array = $this->services_search_folders;
				break;
			case self::TYPE_TRANSLATIONS:
				$search_array = $this->translations_search_folders;
				break;
		}

		return $search_array;
	}
}