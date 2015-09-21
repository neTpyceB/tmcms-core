<?php

namespace neTpyceB\TMCms\Config;

use neTpyceB\TMCms\Traits\singletonInstanceTrait;

/**
 * Class Configuration
 * @package neTpyceB\TMCms\Config
 */
class Configuration
{
    use singletonInstanceTrait;

    /**
     * @var array
     */
    public static $params = [];
    /**
     * @var string
     */
    public static $current_env = 'prod';

    /**
     * @param string $env
     * @return $this
     */
    public function addConfigurationEnv($env = 'prod')
    {
        if (!isset(static::$params[$env])) {
            static::$params[$env] = [];
        }

        $params = include(DIR_CONFIGS . $env . '.php');
        self::$params[$env] = array_merge(static::$params[$env], $params);

        return $this;
    }

    /**
     * @param string $env
     * @return $this
     */
    public function setCurrentEnv($env)
    {
        self::$current_env = $env;

        return $this;
    }

    /**
     * @param string $key
     * @param string $env
     * @return string
     */
    public function get($key, $env = NULL)
    {
        if (!$env) {
            $env = self::$current_env;
        }

        return isset(self::$params[$env][$key]) ? self::$params[$env][$key] : NULL;
    }
}