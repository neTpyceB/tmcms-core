<?php

namespace TMCms\Config;

use TMCms\Traits\singletonInstanceTrait;

/**
 * Class Configuration
 * @package TMCms\Config
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
     * @param string $env with filename from /configs/ folder
     * @param array $params - can supply with params, will not read file with $env
     * @return $this
     */
    public function addConfigurationEnv($env = 'prod', array $params = [])
    {
        if (!isset(static::$params[$env])) {
            static::$params[$env] = [];
        }

        $params = $params ? $params : include(DIR_CONFIGS . $env . '.php');
        self::$params[$env] = array_merge(static::$params[$env], $params);

        $this->setCurrentEnv($env);

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
     * @return string
     */
    public function getCurrentEnv()
    {
        return self::$current_env;
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