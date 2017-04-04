<?php
declare(strict_types=1);

namespace TMCms\Background;

use Exception;
use RuntimeException;

defined('INC') or exit;

class Process
{
    /**
     * @var callable
     */
    private $function_code;
    /**
     * @var int
     */
    private $pid;

    /**
     * @param string $function_code to execute
     */
    public function __construct(string $function_code = '')
    {
        if (!$function_code) {
            // Means that we use PID for resuming existing process
            return;
        }

        $this->function_code = $function_code;
    }

    /**
     * @param int $pid PID of process to resume
     *
     * @return self
     */
    static public function continueFromPID($pid)
    {
        $process = new self();
        $process->setPid($pid);

        return $process;
    }

    /**
     * Runs the function in a background process.
     *
     * @param string $output_file File to write the output of the process to
     *                            defaults is /dev/null
     *                            Windows do not us it, so no effect on that OS
     * @param bool   $append      the output to file
     */
    public function runProcess($output_file = '/dev/null', $append = false)
    {
        switch (PHP_OS) {
            case 'Windows':
                shell_exec(sprintf('%s &', $this->function_code, $output_file));
                break;
            case 'Linux':
                $this->pid = (int)shell_exec(sprintf('php -r "%s" %s %s 2>&1 & echo $!', str_replace('"', '\"', $this->function_code), ($append) ? '>>' : '>', $output_file));
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Could not execute command "%s" because operating system "%s" is not supported', $this->function_code, PHP_OS
                ));
        }
    }

    /**
     * Check the process is still running or not.
     *
     * @return bool
     */
    public function isRunning()
    {
        $this->isCurrentOsSupported('Only on *nix-based process can be checked. You have "%s".');
        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $this->pid));
            if (count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/', $result)) {
                return true;
            }
        } catch (Exception $e) {

        }

        return false;
    }

    /**
     * @param string $message Exception message if the OS is not supported
     *
     * @throws RuntimeException if the operating system is not supported by Cocur\BackgroundProcess
     *
     * @codeCoverageIgnore
     */
    protected function isCurrentOsSupported($message)
    {
        if (PHP_OS !== 'Linux') {
            throw new RuntimeException(sprintf($message, PHP_OS));
        }
    }

    /**
     * Destroy the process.
     *
     * @return bool
     */
    public function stopProcess()
    {
        $this->isCurrentOsSupported('Only on *nix-based systems process cna be destroyed. You are running "%s".');
        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $this->pid));
            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } catch (Exception $e) {

        }

        return false;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        $this->isCurrentOsSupported('Only on *nix-based systems process can return PID. You are running "%s".');

        return $this->pid;
    }

    protected function setPid($pid)
    {
        $this->pid = $pid;
    }
}