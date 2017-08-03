<?php

use TMCms\DB\SQL;
use TMCms\Log\Errors;

if (!defined('INC')) {
	define('INC', true);
}
if (!defined('MODE')) {
	define('MODE', 'site');
}

if (stripos(USER_AGENT, 'bot') !== false) {
	return;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$stack = isset($_GET['stack']) ? $_GET['stack'] : '';
$url = isset($_GET['url']) ? $_GET['url'] : '';
$line = isset($_GET['line']) ? $_GET['line'] : '';

Errors::notify_devs(
	CFG_DOMAIN .' JavaScript error',
	'Message: '. $msg
	."\nClicks Stack: ". $stack
	."\nURL: ". $url
	."\nLine: ". $line
);

\TMCms\Log\Entity\ErrorLogEntityRepository::getInstance();
// Write log if DB available
if (!SQL::getInstance()->getConnectionHandler()) return;
q('INSERT INTO `cms_error_log` (
    `ts`, `ip_long`, `agent`,
    `type`, `msg`, `file`,
    `line`, `vars`
) VALUES (
    "' . NOW . '", "' . ip2long(IP) . '", "' . USER_AGENT . '",
    "JS", "' . sql_prepare($msg) . '", "' . sql_prepare($url) . '",
    "' . sql_prepare($line) . '", "' . sql_prepare($stack) . '"
)');