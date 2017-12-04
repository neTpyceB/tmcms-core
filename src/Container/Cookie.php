<?php
declare(strict_types=1);

namespace TMCms\Container;

use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class Cookie
 * @package TMCms\Container
 */
class Cookie extends Base
{
    use singletonInstanceTrait;

    public function __construct()
    {
        parent::__construct($_COOKIE);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setValue(string $key, $value)
    {
        parent::setValue($key, $value);

        setcookie($key, $value, 86400, '/'); // One day

        return $this;
    }
}
