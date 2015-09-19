<?php

namespace neTpyceB\TMCms\Traits;

defined('INC') or exit;

/**
 * Trait singletonInstanceTrait means that class MAY be used as singleton to get created instance.
 * May be used in any required class
 * @package neTpyceB\TMCms\Traits
 */
trait singletonInstanceTrait
{
    /**
     * @var $this
     */
    private static $instance;

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}