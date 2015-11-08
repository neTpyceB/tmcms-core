<?php

namespace neTpyceB\TMCms\Cache;

use Memcached;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

/**
 * Class MemcachedCache
 * @package neTpyceB\TMCms\Cache
 */
class MemcachedCache implements ICache
{
    use singletonInstanceTrait;

    const HOST = 'localhost';
    const PORT = 11211;

    /** @var Memcached connected instance */
    private static $memcached = null;

    /**
     * @return MemcachedCache
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$memcached = new Memcached();
            self::$memcached->addServer(self::HOST, self::PORT);

        }
        return self::$instance;
    }

    public function disconnect() {
        self::$memcached = NULL;
    }

    /**
     * @return bool
     */
    public static function itWorks()
    {
        return class_exists('Memcached');
    }

    /**
     * @param string $key
     * @return bool|string[]
     */
    public function delete($key)
    {
        $this->set($key, NULL, 0);
        return self::$memcached->delete(CFG_DOMAIN . $key);
    }

    /**
     * Updates existing value or creates new by key
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return array|bool
     */
    public function set($key, $value, $ttl = 2592000)
    {
        $res = NULL;
        // Try to update existing
        if ($this->exists($key)) {
            $res = self::$memcached->set(CFG_DOMAIN . $key, $value, $ttl);
        } else {
            // Set new
            $res = $this->add($key, $value, $ttl);
        }
        return $res;
    }

    /**
     * @param string $key
     * @return bool|string[]
     */
    public function exists($key)
    {
        return (bool)$this->get($key);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        $res = self::$memcached->get(CFG_DOMAIN . $key);
        if ($res === false) {
            $res = NULL; // Return NULL if not found
        }

        return $res;
    }

    /**
     * Add new value
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return bool
     */
    private function add($key, $value, $ttl = 2592000)
    {
        $res = self::$memcached->add(CFG_DOMAIN . $key, $value, $ttl);
        return $res;
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        return self::$memcached->flush();
    }

    /**
     * @param $key
     * @param int $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        if ($this->exists($key)) {
            return self::$memcached->increment($key, $value);
        } else {
            return $this->set($key, $value);
        }
    }

    /**
     * @param mixed $key
     * @param int $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        if ($this->exists($key)) {
            return self::$memcached->decrement($key, $value);
        } else {
            return $this->set($key, -$value);
        }
    }

    public function getResultCode()
    {
        return self::$memcached->getResultCode();
    }
}