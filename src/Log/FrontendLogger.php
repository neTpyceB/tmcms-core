<?php

namespace TMCms\Log;

use TMCms\Log\Entity\FrontLogEntity;

/**
 * Class FrontendLogger
 * @package TMCms\Log
 */
class FrontendLogger implements ILogger
{
    private $stack = [];
    /**
     * @var $this
     */
    private static $instance;
    /**
     * @var string
     */

    /**
     * Singleton
     */
    private function __construct()
    {

    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * @param string $str
     * @param string $flag
     */
    public function write($str = '', $flag = ILogger::WRITE_LOG)
    {
        $this->stack[] = $str;
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

    public function startLog()
    {
        $this->stack = [];
    }

    public function endLog()
    {
        foreach ($this->stack as $v) {
            $log = new FrontLogEntity();
            $log->loadDataFromArray([
                'ts' => NOW,
                'text' => $v,
            ]);
            $log->save();
        }
    }

    public function __destruct()
    {
        $this->endLog();
    }
}