<?php

namespace TMCms\Log;

/**
 * Interface ILogger
 * @package TMCms\Log
 */
interface ILogger {

    const WRITE_LOG = '*';

    const WRITE_ERR = 'E';

    /**
     * @return mixed
     */
    public function startLog();

    /**
     * @param string $str
     */
    public function log($str);

    /**
     * @param string $str
     */
    public function err($str);

    /**
     * @param string $str
     * @param string $flag
     */
    public function write($str = '', $flag = ILogger::WRITE_LOG);

    /**
     * @return mixed
     */
    public function endLog();
}