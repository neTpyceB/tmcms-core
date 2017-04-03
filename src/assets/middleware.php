<?php
//declare(strict_types=1);
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.3
 */

use TMCms\Config\Settings;
use TMCms\Middleware\MiddlewareHandler;

$handler = MiddlewareHandler::getInstance();
if (Settings::get('middleware_throttle_limit')) {
    require __DIR__ . '/../Middleware/handlers/ThrottleMiddleware.php';
    $handler->registerHandler('before_frontend_init', 'ThrottleMiddleware', 'run', ['limit' => Settings::get('middleware_throttle_limit')]);
}