<?php
declare(strict_types=1);

use TMCms\Config\Settings;
use TMCms\Log\FrontendLogger;
use TMCms\Routing\Interfaces\IMiddleware;

class RestrictIpMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        if (!isset($params['ips'])) {
            return;
        }

        // IPs are separated by newline
        $ips = explode("\n", $params['ips']);
        // Remove empty lines and odd spaces
        foreach ($ips as & $v) {
            $v = trim($v);
        }
        unset($v);

        // If still have IP in range and client is blocked - show error
        if ($ips && !in_array(IP, $ips, true)) {

            if (Settings::isFrontendLogEnabled()) {
                FrontendLogger::getInstance()->err('IP ' . IP . ' forbidden');
            }

            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }
            die('Error 403. Forbidden');
        }
    }
}