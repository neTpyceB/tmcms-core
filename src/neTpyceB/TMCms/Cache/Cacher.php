<?php

namespace neTpyceB\TMCms\Cache;

use neTpyceB\TMCms\Log\Usage;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

/**
 * Class Cacher
 * @package neTpyceB\TMCms\Cache
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

        if (FileCache::itWorks()) {
            $this->getFileCacher()->deleteAll();
        }

        if (MemcachedCache::itWorks()) {
            $this->getMemcachedCacher()->deleteAll();
        }

        if (MemcacheCache::itWorks()) {
            $this->getMemcacheCacher()->deleteAll();
        }

        if (FakeCache::itWorks()) {
            $this->getFakeCacher()->deleteAll();
        }
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
     * @return MemcachedCache
     */
    public function getFakeCacher()
    {
        return call_user_func([__NAMESPACE__ . '\FakeCache', 'getInstance']);
    }
}