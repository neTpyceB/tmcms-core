<?php
declare(strict_types=1);
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.1
 */

namespace TMCms\Cache;

use Redis;
use TMCms\Cache\Interfaces\ICache;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class RedisCache
 * @package TMCms\Cache
 */
class RedisCache implements ICache
{
    use singletonInstanceTrait;

    const HOST = 'localhost';
    const PORT = 6379;

    /** @var Redis connected instance */
    private static $redis = NULL;

    /**
     * @return bool
     */
    public static function itWorks(): bool
    {
        if (class_exists('Redis')) {
            self::getInstance();

            if ('PONG' == self::$redis->ping()) {
                return true;
            }
        };

        return false;
    }

    /**
     * @return RedisCache
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$redis = new Redis();
            self::$redis->connect(self::HOST, self::PORT);

        }

        return self::$instance;
    }

    public function disconnect()
    {
        self::$redis->close();
        self::$redis = NULL;
        self::$instance = NULL;
    }

    /**
     * @param string $key
     */
    public function delete($key): void
    {
        self::$redis->delete(CFG_DOMAIN . $key);
    }

    /**
     * @param string $key
     *
     * @return string|NULL
     */
    public function get($key): ?string
    {
        if (!self::$instance) {
            self::getInstance();
        }

        $res = self::$redis->get(CFG_DOMAIN . $key);
        if ($res === false) {
            $res = NULL; // Return NULL if not found
        }

        return $res;
    }

    /**
     * @return bool true
     */
    public function deleteAll(): bool
    {
        return self::$redis->flushAll();
    }

    /**
     * @param     $key
     * @param int $value
     *
     * @return int the new value
     */
    public function increment($key, $value = 1): int
    {
        if ($this->exists($key)) {
            return self::$redis->incrBy($key, $value);
        } else {
            $this->set($key, $value);

            return $value;
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key): bool
    {
        return self::$redis->exists($key);
    }

    /**
     * Updates existing value or creates new by key
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 2592000): bool
    {
        if (!self::$instance) {
            self::getInstance();
        }

        return self::$redis->set(CFG_DOMAIN . $key, $value, $ttl);
    }

    /**
     * @param     $key
     * @param int $value
     *
     * @return int the new value
     */
    public function decrement($key, $value = 1): int
    {
        if ($this->exists($key)) {
            return self::$redis->decrBy($key, $value);
        } else {
            $this->set($key, -$value);

            return -$value;
        }
    }
}