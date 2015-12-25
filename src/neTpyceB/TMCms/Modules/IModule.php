<?php

namespace neTpyceB\TMCms\Modules;

/**
 * Interface IModule
 * @package neTpyceB\TMCms\Modules
 *
 * @property array $tables
 */
interface IModule
{
    /**
     * @return $this
     */
    public static function getInstance();
}