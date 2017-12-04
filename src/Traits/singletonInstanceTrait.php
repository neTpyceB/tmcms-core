<?php
declare(strict_types=1);

namespace TMCms\Traits;

use Exception;
use RuntimeException;

\defined('INC') or exit;

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
    protected static $instance;

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * @throws Exception to prevent cloning object.
     */
    public function __clone()
    {
        throw new RuntimeException('You cannot clone singleton object');
    }
}
