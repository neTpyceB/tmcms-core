<?php

namespace TMCms\Config;

use TMCms\Cache\Cacher;
use TMCms\Cache\MemcacheCache;
use TMCms\Cache\MemcachedCache;
use TMCms\Config\Entity\SettingEntity;
use TMCms\Config\Entity\SettingEntityRepository;
use TMCms\DB\SQL;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class Settings
 */
class Settings
{
    use singletonInstanceTrait;

    const CACHE_KEY = 'cms_settings_data';

    /**
     * @var array
     */
    private static $_cached_settings = [];

    /**
     * @return bool
     */
    public static function isProductionState()
    {
        return self::get('production');
    }

    /**
     * Get one setting value by name
     * @param $name
     * @return string|NULL
     */
    public static function get($name)
    {
        if (isset(self::$_cached_settings[$name])) {
            return self::$_cached_settings[$name];
        }
        return NULL;
    }

    /**
     * @return bool
     */
    public static function isCacheEnabled()
    {
        return self::get('common_cache');
    }

    /**
     * @return bool
     */
    public static function isFrontendLogEnabled()
    {
        return self::get('save_frontend_log');
    }

    /**
     * @return string
     */
    public static function getCommonEmail()
    {
        return self::get('common_email');
    }

    /**
     * @return string
     */
    public static function getDefaultDateFormat()
    {
        $format = self::get('common_date_format');
        if (!$format) {
            $format = CFG_CMS_DATETIME_FORMAT;
        }
        return $format;
    }

    /**
     * Init all settings
     * @param bool $no_cache fetch data from cache or not
     * @return array
     */
    public function init($no_cache = false)
    {
        $this->bootCacher();

        // From local cache
        if (self::$_cached_settings && is_array(self::$_cached_settings) && !$no_cache) {
            return self::$_cached_settings;
        }

        if ($no_cache) {
            // Force cache invalidation
            self::$_cached_settings = [];
        } else {
            // Get from cache
            self::$_cached_settings = Cacher::getInstance()->getDefaultCacher()->get(self::CACHE_KEY);
        }

        if (!self::$_cached_settings && SQL::tableExists('cms_settings')) {
            // Get from DB
            $settings_collection = new SettingEntityRepository();
            self::$_cached_settings = $settings_collection->getPairs('value', 'name');
            Cacher::getInstance()->getDefaultCacher()->set(self::CACHE_KEY, self::$_cached_settings);
        }

        if (!is_array(self::$_cached_settings)) {
            self::$_cached_settings = [];
        }

        return self::$_cached_settings;
    }

    /**
     * Set current default cache class
     */
    private function bootCacher()
    {
        /** @var Cacher $cacher */
        $cacher = Cacher::getInstance();

        // Default cacher if FileCache, so we do not check it
        if (MemcachedCache::itWorks()) { // Distributed cache new version
            $cacher->setDefaultCacher('MemcachedCache');
        } elseif (MemcacheCache::itWorks()) { // Distributed cache old version
            $cacher->setDefaultCacher('MemcacheCache');
        }
    }

    /**
     * Set new setting or update existing
     * @param $name
     * @param string $value
     * @return bool
     */
    public function set($name, $value = '')
    {
        if (!$name) {
            return false;
        }

        /** @var SettingEntity $setting */
        $setting = SettingEntityRepository::findOneEntityByCriteria(['name' => $name]);

        if (!$setting) {
            $setting = new SettingEntity();
            $setting->setName($name);
        }
        $setting->setValue($value);
        $setting->save();

        // Update cache
        self::$_cached_settings[$name] = $value;
        Cacher::getInstance()->getDefaultCacher()->delete(self::CACHE_KEY);

        return true;
    }

    /**
     * Removes setting by prefix, e.g. m_gallery_...
     * @param string $prefix
     * @param bool $skip_modules
     * @return \PDOStatement
     */
    public function clear($prefix = '', $skip_modules = false)
    {
        // Delete from database
        $settings_collection = new SettingEntityRepository();
        if ($prefix) {
            $settings_collection->setWherePrefix($prefix);
        }
        if ($skip_modules) {
            $settings_collection->setSkipModules();
        }

        $settings_collection->deleteObjectCollection();

        // Clear cache
        Cacher::getInstance()->getDefaultCacher()->delete(self::CACHE_KEY);

        return true;
    }
}