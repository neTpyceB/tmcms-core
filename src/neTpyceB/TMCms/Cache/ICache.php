<?php

namespace neTpyceB\TMCms\Cache;

/**
 * Interface ICache
 * @package neTpyceB\TMCms\Cache
 */
interface ICache
{
    /**
     * Return is this cache works in system
     */
    public static function itWorks();

    /**
     * Insert or update value by key
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return
     */
    public function set($key, $value, $ttl = 2592000);

    /**
     * Get value by key
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Check if key is in cache without getting it's value
     * @param string $key
     * @return bool
     */
    public function exists($key);

    /**
     * Remove value from cache by key
     * @param string $key
     */
    public function delete($key);

    /**
     * Clear entire cache
     */
    public function deleteAll();

    public function disconnect();
}