<?php
declare(strict_types=1);

namespace TMCms\Background;

use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class ProcessQueue
 * @package TMCms\Background
 *
 * @usage   - code should be supplied as string only
 * $file_path = DIR_BASE . 'log.txt';
 * $time_sleep = 15;
 * $write = '
 * sleep('. $time_sleep .');
 * file_put_contents("'. $file_path .'", '. NOW .');'
 * ;
 *
 * ProcessQueue::getInstance()->addBackgroundTaskInQueue($write);
 */
class ProcessQueue
{
    use singletonInstanceTrait;

    public function addBackgroundTaskInQueue(string $function_code)
    {
        $process = new Process($function_code);
        $process->runProcess();
    }
}