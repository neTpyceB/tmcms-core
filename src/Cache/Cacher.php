<?php

namespace TMCms\Cache;

use TMCms\Cache\Interfaces\ICache;
use TMCms\Log\Usage;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class Cacher
 * @package TMCms\Cache
 */
class Cacher
{
    use singletonInstanceTrait;

    /**
     * @var string
     */
    private $default_cache_classname = 'FileCache';

    /**
     * @return iCache
     */
    public function getDefaultCacher()
    {
        return call_user_func([__NAMESPACE__ . '\\' . $this->default_cache_classname, 'getInstance']);
    }

    /**
     * @param string $classname
     * @return bool
     */
    public function setDefaultCacher($classname)
    {
        $this->default_cache_classname = $classname;

        return true;
    }

    /**
     * Clears all caches in all available places
     */
    public function clearAllCaches()
    {
        // Save usage for stats
        Usage::getInstance()->add(__CLASS__, __FUNCTION__);

        // File cache contains resize images, very resource consumable operations
        // Do not delete images if FileCache is not default and other caches exist
        $clear_file_cache = true;

        if (MemcachedCache::itWorks()) {
            $this->getMemcachedCacher()->deleteAll();
            $clear_file_cache = false;
        }

        if (MemcacheCache::itWorks()) {
            $this->getMemcacheCacher()->deleteAll();
            $clear_file_cache = false;
        }

        if ($clear_file_cache) {
            if (FileCache::itWorks()) {
                $this->getFileCacher()->deleteAll();
            }
        }

        if (FakeCache::itWorks()) {
            $this->getFakeCacher()->deleteAll();
        }
    }

    /**
     * @return MemcachedCache
     */
    public function getMemcachedCacher()
    {
        return call_user_func([__NAMESPACE__ . '\MemcachedCache', 'getInstance']);
    }

    /**
     * @return MemcacheCache
     */
    public function getMemcacheCacher()
    {
        return call_user_func([__NAMESPACE__ . '\MemcacheCache', 'getInstance']);
    }

    /**
     * @return FileCache
     */
    public function getFileCacher()
    {
        return call_user_func([__NAMESPACE__ . '\FileCache', 'getInstance']);
    }

    /**
     * @return MemcachedCache
     */
    public function getFakeCacher()
    {
        return call_user_func([__NAMESPACE__ . '\FakeCache', 'getInstance']);
    }

    public function disconnect() {

    }
}