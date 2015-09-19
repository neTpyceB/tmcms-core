<?php

namespace neTpyceB\TMCms\Cache;

use neTpyceB\TMCms\Traits\singletonInstanceTrait;

/**
 * Class APCCache
 * @package neTpyceB\TMCms\Cache
 */
class APCCache implements ICache
{
    use singletonInstanceTrait;

    /**
     * @return bool
     */
    public static function itWorks()
    {
        return extension_loaded('apc') && ini_get('apc.enabled');
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        $res = false;
        $data = apc_fetch(CFG_DOMAIN . $key, $res);
        return $res ? $data : NULL;
    }

    /**
     * @param string $key
     * @return bool|string[]
     */
    public function exists($key)
    {
        return apc_exists(CFG_DOMAIN . $key);
    }

    /**
     * @param string $key
     * @return bool|string[]
     */
    public function delete($key)
    {
        $this->set($key, NULL, 0);
        return apc_delete(CFG_DOMAIN . $key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return array|bool
     */
    public function set($key, $value, $ttl = 2592000)
    {
        return apc_store(CFG_DOMAIN . $key, $value, $ttl);
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        return apc_clear_cache('user');
    }
}