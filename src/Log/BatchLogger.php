<?php
declare(strict_types=1);

namespace TMCms\Log;

use TMCms\Files\FileSystem;

/**
 * Used for Async processes - keeps log with one uid within one batch, and flushes to file in correct order
 * Usage $logger = new BatchLogger('process_name', SOME_ID);
 * Class BatchLogger
 */
class BatchLogger implements ILogger
{
    /**
     * @var array
     */
    protected $stringBuffer = [];
    /**
     * @var string
     */
    protected $file = '';
    /**
     * @var string
     */
    protected $indent = '';

    /**
     * @param string $str
     * @param string $flag
     */
    public function write($str = '', $flag = ILogger::WRITE_LOG)
    {
        $str = date('d-m-Y H:i:s:u') . " | {$flag} | #{$this->indent}#: {$str}";
        $this->stringBuffer[] = $str;
    }

    /**
     * @param string $str
     */
    public function log($str = '')
    {
        $this->write($str, self::WRITE_LOG);
    }

    /**
     * @param string $str
     */
    public function err($str = '')
    {
        $this->write($str, self::WRITE_ERR);
    }

    /**
     *
     */
    public function startLog()
    {
        $this->stringBuffer = [];
    }

    /**
     *
     */
    public function endLog()
    {
        // Create log directory
        if (!file_exists(DIR_FRONT_LOGS . $this->file)) {
            FileSystem::mkDir(\pathinfo(DIR_FRONT_LOGS . $this->file, \PATHINFO_DIRNAME));
        }
        // Create log file
        if (!file_exists(DIR_FRONT_LOGS . $this->file)) {
            \touch(DIR_FRONT_LOGS . $this->file);
        }

        if (\count($this->stringBuffer)) {
            error_log(rtrim(join(PHP_EOL, $this->stringBuffer), PHP_EOL) . PHP_EOL, 3, DIR_FRONT_LOGS . $this->file);
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->endLog();
    }

    /**
     * @param $file
     * @param $indent
     */
    public function __construct($file, $indent)
    {
        $this->file = $file;
        $this->indent = $indent;
    }

}
