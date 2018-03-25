<?php
declare(strict_types=1);

namespace TMCms\Log;

use TMCms\Config\Configuration;
use TMCms\Config\Constants;
use TMCms\Config\Settings;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\Log\Entity\ErrorLogEntity;
use TMCms\Log\Entity\ErrorLogEntityRepository;

defined('INC') or exit;

/**
 * Class Errors
 */
class Errors
{
    /**
     * @var array
     */
    private static $trace_stack = [];

    /**
     * @var array
     */
    private static $error_number_names = [
        1     => 'E_ERROR',
        2     => 'E_WARNING',
        4     => 'E_PARSE',
        8     => 'E_NOTICE',
        16    => 'E_CORE_ERROR',
        32    => 'E_CORE_WARNING',
        64    => 'E_COMPILE_ERROR',
        128   => 'E_COMPILE_WARNING',
        256   => 'E_USER_ERROR',
        512   => 'E_USER_WARNING',
        1024  => 'E_USER_NOTICE',
        2048  => 'E_STRICT',
        4096  => 'E_RECOVERABLE_ERROR',
        8192  => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
        32767 => 'E_ALL',
    ];

    /**
     * Outputs dump of $data in browser
     *
     * @param mixed $data
     * @param bool  $clean
     *
     * @throws \RuntimeException
     */
    public static function dump($data, bool $clean = true)
    {
        if ($clean) {
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            while (ob_get_level() && @ob_end_clean() !== false) {
                // Do nothing, just clean buffer
            }
        }

        // Represent supplied data
        switch (gettype($data)) {
            case 'boolean':

                echo 'Boolean: ' . ($data ? 'true' : 'false') . '<br>' . PHP_EOL;

                break;

            case 'string':

                echo 'String: ' . $data . '<br>'. PHP_EOL;

                break;

            case 'double':

                echo 'Double: ' . $data . '<br>'. PHP_EOL;

                break;

            case 'integer':

                echo 'Integer: ' . $data . '<br>'. PHP_EOL;

                break;

            case 'object':

                $methods = get_class_methods($data);

                if ($methods) {

                    natsort($methods);

                    $html = ['<table style="margin-left:50px">'];

                    foreach ($methods as $v) {
                        $html[] = '<tr><td>' . htmlspecialchars($v) . '()</td></tr>';
                    }

                    $html[] = '</table>';

                    $methods = implode('', $html);
                }

                $vars = get_object_vars($data);

                if ($vars) {

//                    natsort($vars);

                    $html = ['<table style="margin-left:50px">'];

                    foreach ($vars as $k => $v) {
                        $html[] = '<tr><td>' . htmlspecialchars((string)$k) . '</td><td>' . (is_string($v) ? htmlspecialchars($v) : '') . '</td></tr>';
                    }

                    $html[] = '</table>';

                    $vars = implode('', $html);
                }

                echo 'Object: <strong>' . get_class($data) . '</strong>' . ($methods ? '<br><br><strong>Methods</strong>: ' . $methods : '') . ($vars ? '<br><br><strong>Variables</strong>: ' . $vars : '') . '<br><br><strong>Data</strong>:<br><pre>' . print_r($data, true) . '</pre>';

                break;

            case 'resource':

                echo 'Resource: ' . get_resource_type($data) . '<br>'. PHP_EOL;

                break;

            case 'NULL':

                echo 'NULL'. PHP_EOL . '<br>';

                break;

            case 'array':

                echo '<pre>' . print_r($data, true) . '</pre>' . '<br>'. PHP_EOL;

                break;

            default:

                throw new \RuntimeException('Supplied data type is not supported');
        }

        self::outputDebugStackTable();

        // Show
        if (Settings::get('debug_panel')) {
            Stats::getView();
        }

        // No more output
        if ($clean) {
            exit;
        }
    }

    /**
     * Shows error alert and goes back
     */
    public static function outputDebugStackTable()
    {
        self::prepareTraceStack();

        // For terminals
        if (IS_CLI || IS_AJAX_REQUEST) {
            foreach (self::$trace_stack as $step) {
                echo 'Class: ' . (isset($step['class']) ? $step['class'] . $step['type'] : '') . $step['function'] . '<br>';
                echo "\n";

                echo 'File: ' . ($step['file'] ?? '') . ':' . ($step['line'] ?? '') . '<br>';
                echo "\n\n";
            }

            return;
        }

        ?>
        <style>
            table {
                border-collapse: collapse
            }

            .trace_table td {
                padding: 5px 15px;
            }

            .trace_table tr:hover {
                background-color: lightgrey;
            }

            .liner_right {
                border-right: 1px solid lightgrey;
            }

            .liner_bottom {
                border-bottom: 1px solid lightgrey;
            }
        </style>

        <table class="trace_table" width="100%">
            <tr>
                <td class="liner_right liner_bottom"><strong>Class and method</strong></td>
                <td class="liner_right liner_bottom"><strong>File</strong></td>
                <td class="liner_right liner_bottom"><strong>Line</strong></td>
            </tr>
            <?php
            $dir_base_length = strlen(DIR_BASE) - 1;

            foreach (self::$trace_stack as $step) {
                echo '<tr>
                    <td>' . (isset($step['class']) ? '' . $step['class'] . '' . $step['type'] : '') . '' . $step['function'] . '</td>
                    <td>' . (isset($step['file']) ? substr($step['file'], $dir_base_length) : '') . '</td>
                    <td>' . ($step['line'] ?? '') . '</td>
                </tr>';
            }
            ?>
        </table>
        <?php
    }

    private static function prepareTraceStack()
    {
        if (!self::$trace_stack) {
            self::$trace_stack = array_slice(array_reverse(debug_backtrace()), 0, -2); // Cut 2 calls of function itself
        }
    }

    /**
     * Shows Javascript error alert
     *
     * @param string $str
     *
     * @throws \RuntimeException
     */
    public static function error(string $str)
    {
        // Reloads current page with the same error, prevent endless loop
        if (SELF === REF) {
            throw new \RuntimeException('Looping references');
        }

        if (IS_AJAX_REQUEST) {
            // Return as string for ajax response
            echo $str;
        } else {
            // Run as Javascript alert
            ?>
            <script type="text/javascript">alert('<?= $str ?>');
                if (history.length) {
                    // Go back
                    history.back();
                }
                // Reload page
                window.location = '<?= REF ?>'
            </script>
            <?php
        }
        exit;
    }

    /**
     * @param int    $e_no
     * @param string $e_str
     * @param string $e_file
     * @param string $e_line
     */
    public static function Handler($e_no = 0, string $e_str = '', string $e_file = '', string $e_line = '')
    {
        if (error_reporting() === 0) {
            // Continue script execution, skipping standard PHP error handler using @ suppression
            return;
        }

        // Prevent loops
        if (defined('SKIP_SHUTDOWN_ERROR_HANDLER')) {
            return;
        }
        define('SKIP_SHUTDOWN_ERROR_HANDLER', 1); // Only once

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        while (ob_get_level() && @ob_end_clean() !== false) {
            // Do nothing, just clean buffer
        }

        if(is_object($e_no)){
            $e_str = $e_no->getMessage();
            $e_file = $e_no->getFile();
            $e_line = $e_no->getLine();
            self::$trace_stack = $e_no->getTrace();
            $e_no = $e_no->getCode();
        }

        self::writeLog($e_no, $e_str, $e_file, (string)$e_line);

        self::prepareTraceStack();

        // Development mode shows error, production mode send email to developers
        if (!Settings::isProductionState()) {
            if (MODE === 'background') {
                // Show as terminal texts
                self::ErrorHandlerPlain($e_no, $e_str, $e_file, (string)$e_line);
            } else {
                // Show as table for browser
                self::ErrorHandlerHTML($e_no, $e_str, $e_file, (string)$e_line);
            }
        } elseif (!Settings::get('do_not_send_php_errors')) { // Check that we may send emails
            ob_start();

            self::ErrorHandlerPlain($e_no, $e_str, $e_file, (string)$e_line);

            $message = ob_get_clean();

            self::sendErrorToDevelopers(CFG_DOMAIN . ' PHP Error', $message);
        }

        // Stop further execution
        exit;
    }

    /**
     * @param int    $errNo
     * @param string $errStr
     * @param string $errFile
     * @param string $errLine
     */
    private static function writeLog($errNo, string $errStr, string $errFile, string $errLine)
    {
        if (!SQL::getInstance()->getConnectionHandler()) {
            // No Db connected
            return;
        }

        // Pre-create database
        new ErrorLogEntityRepository;

        $log = new ErrorLogEntity();
        $log->loadDataFromArray([
            'file' => $errFile,
            'line' => $errLine,
            'msg'  => self::getErrorTextByNumber($errNo) . ':' . $errStr,
            'type' => 'PHP',
            'vars' => serialize(['session' => $_SESSION, 'post' => $_POST, 'get' => $_GET, 'cookie' => $_COOKIE]),
        ]);
        $log->save();
    }

    /**
     * @param int $number
     *
     * @return string
     */
    private static function getErrorTextByNumber($number): string
    {
        return self::$error_number_names[$number] ?? '';
    }

    /**
     * @param int    $e_no
     * @param string $e_str
     * @param string $e_file
     * @param string $e_line
     */
    private static function ErrorHandlerPlain($e_no, string $e_str, string $e_file, string $e_line)
    {
        echo "\n", self::getErrorTextByNumber($e_no);

        $e_str = strpos($e_str, ':::') === false ? ['', $e_str] : explode(':::', $e_str);
        if ($e_str[0] === 'sql') {
            echo "\n\n" . SQL::getLastError();
        }

        echo "\n\n" . $e_str[1] . "\n" . $e_file . ' : ' . $e_line . "\n\n";

        $dir_base_length = strlen(DIR_BASE) - 1;

        self::prepareTraceStack();

        foreach (self::$trace_stack as $step) {
            echo (isset($step['class']) ? $step['class'] . $step['type'] : '') . $step['function'] . ' |  ' . (isset($step['file']) ? substr($step['file'], $dir_base_length) : '') . ' : ' . ($step['line'] ?? '') . "\n";
        }

        echo "\n";

        self::printGlobalArraysParams();
    }

    private static function printGlobalArraysParams()
    {
        foreach ([
                     '$_GET'     => $_GET,
                     '$_POST'    => $_POST,
                     '$_FILES'   => $_FILES,
                     '$_COOKIE'  => $_COOKIE,
                     '$_SESSION' => $_SESSION,
                 ] as $k => $v) {
            if (!$v) {
                continue;
            }

            echo '<fieldset><legend onclick="document.getElementById(\'inner_' . $k . '\').style.display = \'block\'"><strong>' . $k . ' (' . count($v) . ')</strong></legend><div id="inner_' . $k . '" style="display: none"><pre>' . print_r($v, true) . '</pre></div></fieldset>';
        }
    }

    /**
     * Plain text for terminals and error logs
     *
     * @param int    $e_no
     * @param string $e_str
     * @param string $e_file
     * @param string $e_line
     */
    public static function ErrorHandlerHTML($e_no, string $e_str, string $e_file, string $e_line)
    {
        $e_str = strpos($e_str, ':::') === false ? ['', $e_str] : explode(':::', $e_str);

        if (IS_CLI || IS_AJAX_REQUEST) {
            echo self::getErrorTextByNumber($e_no) . "\n";

            if ($e_str[0] === 'sql') {
                echo 'SQL error<br>' . SQL::getLastError() . '<br>' . $e_str[1];
            } else {
                echo 'PHP error in file <strong>' . $e_file . ' : ' . $e_line . '</strong><br>"' . preg_replace('/\"([^"]+)\"/', '"<strong>\\1</strong>"', $e_str[1]) . '"';
                echo "\n" . 'Location: ' . $e_file . ':' . $e_line . "\n";

                echo 'Error: ' . $e_str[1] . "\n";

                self::outputDebugStackTable();

                return;
            }
        }

        echo '<html><head><title>Error</title><style>table {border-collapse:collapse}</style></head><body>
<fieldset><legend>Error ' . self::getErrorTextByNumber($e_no), '</legend><br><table width="100%"><tr><td valign="top">';

        if ($e_str[0] === 'sql') {
            echo 'SQL error<br>' . SQL::getLastError() . '<br>' . $e_str[1];
        } else {
            echo preg_replace('/\"([^"]+)\"/', '"<strong>\\1</strong>"', $e_str[1]) . '';
        }

        echo '<br><br>File <strong>' . $e_file . ' : ' . $e_line . '</strong> </td><td valign="top">';

        self::outputDebugStackTable();

        echo '<br>';

        self::printGlobalArraysParams();

        echo '</fieldset></body></html>';
    }

    /**
     * Sends error report to developers when error occurs
     *
     * @param string $title
     * @param string $msg
     * @param string $flag_file
     */
    public static function sendErrorToDevelopers($title, $msg, $flag_file = '')
    {
        $cfg_email = Configuration::getInstance()->get('site')['email'];

        if (!$flag_file) {
            $flag_file = md5($msg);
        }

        FileSystem::mkDir(DIR_CACHE . 'errors/');
        $file = DIR_CACHE . 'errors/' . $flag_file;

        if (!file_exists($file) || filemtime($file) + 3600 < NOW) {
            touch($file);
            file_put_contents($file, $msg);

            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            /** @noinspection UsageOfSilenceOperatorInspection */
            @mail(CMS_SUPPORT_EMAIL, $title, $msg . "\n\nAuto Error Report " . Constants::ADMIN_CMS_NAME . ".\n\nVisitor:\nIP: " . $_SERVER['REMOTE_ADDR'] . "\nBrowser agent: " . $_SERVER['HTTP_USER_AGENT'] . "\nURL: " . $_SERVER['REQUEST_URI'] . "\nReferer: " . $_SERVER['HTTP_REFERER'] . "\n" . date('d.m.Y H:i:s'), 'From: ' . $cfg_email, '-f ' . $cfg_email);
        }
    }
}
