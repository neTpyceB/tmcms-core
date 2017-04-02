<?php

namespace TMCms\Cache;

use TMCms\Cache\Interfaces\ICache;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class FakeCache
 * @package TMCms\Cache
 */
class FakeCache implements ICache
{
    use singletonInstanceTrait;

    /**
     * @return bool
     */
    public static function itWorks()
    {
        return true;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     * @return mixed $value
     */
    public function set($key, $value, $ttl = 2592000)
    {
        return true;
    }

    /**
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return NULL;
    }

    /**
     * @param string $key
     * @return bool false
     */
    public function exists($key)
    {
        return false;
    }

    /**
     * @param string $key
     * @return bool true
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function disconnect()
    {
        return true;
    }
}