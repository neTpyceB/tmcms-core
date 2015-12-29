<?php

namespace TMCms\Modules;

/**
 * Interface IModule
 * @package TMCms\Modules
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