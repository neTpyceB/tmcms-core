<?php

namespace neTpyceB\TMCms\Files;

use neTpyceB\TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class Finder used to register custom folder for file loading
 * @package neTpyceB\TMCms\Files
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
	}

	public function addAjaxSearchPath($path)
	{
		$this->ajax_search_folders[] = $path;
	}

	public function addPluginsSearchPath($path)
	{
		$this->plugin_search_folders[] = $path;
	}

	public function addServicesSearchPath($path)
	{
		$this->services_search_folders[] = $path;
	}

	public function addTranslationsSearchPath($path)
	{
		$this->translations_search_folders[] = $path;
	}

	public function searchForRealPath($real_file_path, $type = self::TYPE_ASSETS) {
		$search_array = $this->getPathFolders($type);

		// External path?
		if (($url = @parse_url($real_file_path)) && isset($url['host']) && $url['host'] != CFG_DOMAIN) {
			return $real_file_path;
		}

		// Straight path to local file
		if (file_exists(DIR_BASE . $real_file_path)) {
			return $real_file_path;
		}

		foreach ($search_array as $folder) {
			// Search folders with relative path
			if (file_exists(rtrim(DIR_BASE, '/') . $folder . $real_file_path)) {
				return rtrim(DIR_BASE_URL, '/') . $folder . $real_file_path;
			}

			// Search folders with basename
			$basename = basename($real_file_path);
			if (file_exists(rtrim(DIR_BASE, '/') . $folder . $basename)) {
				return rtrim(DIR_BASE_URL, '/') . $folder . $basename;
			}
		}

		return trigger_error('File "'. $real_file_path .'" with type "'. $type .'" not found');
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