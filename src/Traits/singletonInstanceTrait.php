<?php

namespace TMCms\Traits;

use Exception;

defined('INC') or exit;

/**
 * Trait singletonInstanceTrait means that class MAY be used as singleton to get created instance.
 * May be used in any required class
 * @package TMCms\Traits
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

    /**
     * @throws Exception to prevent cloning object.
     */
    public function __clone()
    {
        throw new Exception('You cannot clone singleton object');
    }
}