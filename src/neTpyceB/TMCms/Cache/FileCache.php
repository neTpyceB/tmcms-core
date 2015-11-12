<?php

namespace neTpyceB\TMCms\Cache;

use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class FileCache
 * @package neTpyceB\TMCms\Cache
 */
class FileCache implements ICache
{
    use singletonInstanceTrait;

    const MAX_TTL = 2147464800;

    /** @var array $name_hashes */
    private static $name_hashes = []; // Cache for md5 hashes

    public static function itWorks()
    {
        return true; // Always works, check access
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = 2592000)
    {
        if (!FileSystem::checkFileName($key)) {
            return false;
        }

        $path = $this->getPathToFile($key);
        if (!$path) {
            return false;
        }

        $file = $path . $key;

        FileSystem::mkDir($path);

        file_put_contents($file, serialize($value));

        touch($file, $this->calculateFileTouchTtl($ttl));

        return is_file($file) && $this->get($key) == $value; // Return true only if file is created and value is stored
    }

    /**
     * @param string $key
     * @return string
     */
    private function getPathToFile($key)
    {
        if (!FileSystem::checkFileName($key)) {
            return NULL;
        }
        return DIR_CACHE . $this->getInnerPath($key);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getInnerPath($name)
    {
        if (!isset(self::$name_hashes[$name])) {
            self::$name_hashes[$name] = md5($name);
        }
        $hash = self::$name_hashes[$name];
        return $hash[0] . '/' . $hash[1] . '/' . $hash[2] . '/';
    }

    /**
     * @param int $ttl
     * @return int
     */
    private function calculateFileTouchTtl($ttl)
    {
        return $ttl ? NOW + $ttl : self::MAX_TTL;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        if (!FileSystem::checkFileName($key)) {
            return NULL;
        }

        if (!$this->exists($key)) {
            return NULL;
        } // This checks cache time and read access

        $path = $this->getPathToFile($key);
        if (!$path) {
            return NULL;
        }

        $file = $path . $key;

        return unserialize(file_get_contents($file));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        if (!FileSystem::checkFileName($key)) {
            return false;
        }

        $path = $this->getPathToFile($key);
        if (!$path) {
            return false;
        }

        $file = $path . $key;
        if (!is_file($file)) {
            return false;
        }

        if (!is_readable($file)) {
            return false;
        } // Check we can access

        // Check cache TTL
        if (filemtime($file) < NOW) {
            // File is old
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        if (!FileSystem::checkFileName($key)) {
            return false;
        }

        $path = $this->getPathToFile($key);
        if (!$path) {
            return false;
        }

        $file = $path . $key;

        if (!is_file($file)) {
            return false;
        }

        unlink($file); // Delete file

        // If no more files in folder with same hash - remove all folder
        if (!count(FileSystem::scanDirs($path))) {
            FileSystem::remdir($path);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        return FileSystem::remdir(DIR_CACHE);
    }

    /**
     * @return bool
     */
    public function disconnect()
    {
        return true;
    }
}