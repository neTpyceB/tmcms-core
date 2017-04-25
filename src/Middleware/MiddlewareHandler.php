<?php
//declare(strict_types=1);
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.2
 */

namespace TMCms\Middleware;

use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class MVC
 */
class MiddlewareHandler
{
    use singletonOnlyInstanceTrait;

    protected $handlers = [
        'before_frontend_init'  => [],
        'after_image_processor' => [],
    ];

    /**
     * @param string $type
     *
     * @param array  $additional_params
     *
     * @return MiddlewareHandler
     */
    public function runHandlersFromType($type, $additional_params = [])
    {
        foreach ($this->handlers[$type] as $ware_data) {
            // Call every registered function with supplied params
            $obj = new $ware_data['class'];
            $obj->{$ware_data['method']}(array_merge($additional_params, $ware_data['params']));
        }

        return $this;
    }

    /**
     * @param string $type
     * @param string $class
     * @param string $method
     * @param array  $params
     *
     * @return MiddlewareHandler
     */
    public function registerHandler($type, $class, $method = 'run', array $params = [])//: MiddlewareHandler
    {
        $this->handlers[$type][] = [
            'class'  => $class,
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }
}