<?php
declare(strict_types=1);

namespace TMCms\Container;

use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class Get
 * @package TMCms\Container
 */
class Get extends Base
{
    use singletonInstanceTrait;

    public function __construct()
    {
        parent::__construct($_GET);
    }
}
