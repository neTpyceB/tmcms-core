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
     * @param string $value
     * @param int $ttl
     * @return $this
     */
    public function setValue(string $key, $value, $ttl = 86400)
    {
        parent::setValue($key, $value);

        setcookie($key, (string)$value, NOW + $ttl, '/'); // One day

        return $this;
    }
}
