<?php
declare(strict_types=1);
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.2
 */

namespace TMCms\Routing;

use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class MVC
 */
class Middleware
{
    use singletonOnlyInstanceTrait;

    protected $handlers = [
        'before_frontend_init' => [],
    ];

    public function runHandlersFromType($type)
    {
        foreach ($this->handlers[$type] as $ware_data) {
            // Call every registered function with supplied params
            $obj = new $ware_data['class'];
            $obj->{$ware_data['method']}($this, ...$ware_data['params']);
        }
    }

    public function registerHandler($type, $class, $method = 'run', $params = [])
    {
        $this->handlers[$type][] = [
            'class'  => $class,
            'method' => $method,
            'params' => $params,
        ];
    }
}