<?php

namespace TMCms\Log;

use TMCms\Config\Settings;
use TMCms\Log\Entity\FrontLogEntity;
use TMCms\Log\Entity\FrontLogEntityRepository;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class FrontendLogger
 * @package TMCms\Log
 */
class FrontendLogger implements ILogger
{
    use singletonInstanceTrait;

    private $stack = [];
    /**
     * @var $this
     */

    /**
     * Singleton
     */
    private function __construct()
    {
        $this->startLog();
    }


    /**
     * @param string $str
     * @param string $flag
     */
    public function write($str = '', $flag = ILogger::WRITE_LOG)
    {
        $this->stack[] = [
            'text' => $str,
            'flag' => $flag,
        ];
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
        // Do nothing if log is disabled
        if (!Settings::get('save_frontend_log')) {
            return;
        }

        new FrontLogEntityRepository; // Check bd exists

        foreach ($this->stack as $v) {
            $log = new FrontLogEntity();
            $log->setText($v['text']);
            $log->setFlag($v['flag']);
            $log->save();
        }
    }

    public function __destruct()
    {
        $this->endLog();
    }
}