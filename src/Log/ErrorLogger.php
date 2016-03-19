<?php

namespace TMCms\Log;

use TMCms\Files\FileSystem;

/**
 * Usage: $logger = new ErrorLogger, $logger->log (alias)
 * Class ErrorLogger
 */
class ErrorLogger implements ILogger
{
    /**
     * @param string $str
     * @param string $flag
     */
    public function write($str = '', $flag = ILogger::WRITE_LOG)
    {
        static $log_id = '';
        if (!$log_id) {
            $log_id = uniqid();
        }

        // Create directory
        if (!file_exists(DIR_FRONT_LOGS)) {
            FileSystem::mkDir(DIR_FRONT_LOGS);
        }

        error_log(date('d.m.Y H:i:s') . "\t{$log_id}\t{$str}\n", 3, DIR_FRONT_LOGS . 'common.log');
    }

    /**
     * @param string $str
     */
    public function log($str)
    {
        $this->write($str, ILogger::WRITE_LOG);
    }

    /**
     * @param string $str
     */
    public function err($str)
    {
        $this->write($str, ILogger::WRITE_ERR);
    }

    /**
     *
     */
    public function startLog()
    {
    }

    /**
     *
     */
    public function endLog()
    {
    }
}