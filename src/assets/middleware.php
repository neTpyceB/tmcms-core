<?php
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.3
 */

use TMCms\Config\Settings;
use TMCms\Middleware\MiddlewareHandler;

$handler = MiddlewareHandler::getInstance();

// Set possible limitations based on domain url
require __DIR__ . '/../Middleware/handlers/DomainLimitationsMiddleware.php';
$handler->registerHandler('before_frontend_init', 'DomainLimitationsMiddleware');

// Limit requests by client IP
if (Settings::get('allowed_ips')) {
    require __DIR__ . '/../Middleware/handlers/RestrictIpMiddleware.php';
    $handler->registerHandler('before_frontend_init', 'RestrictIpMiddleware', 'run', ['ips' => Settings::get('allowed_ips')]);
}

// Limit requests by count
if (Settings::get('middleware_throttle_limit')) {
    require __DIR__ . '/../Middleware/handlers/ThrottleMiddleware.php';
    $handler->registerHandler('before_frontend_init', 'ThrottleMiddleware', 'run', ['limit' => Settings::get('middleware_throttle_limit')]);
}