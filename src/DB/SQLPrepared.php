<?php

namespace TMCms\DB;

defined('INC') or exit;

/**
 * Class SQLPrepared
 */
class SQLPrepared
{

    private $sql, $params;
    /**
     * @var array
     */
    private static $replacements = ['s', 'i', 'ui', 'f', 'uf', 'like', 'l', 'ai', 'aui', 'as', 'af', 'auf', 'string', 'int', 'uint', 'float', 'ufloat', 'arrayint', 'arrayuint', 'arraystring', 'arrayfloat', 'arrayufloat'];

    /**
     * @param string $sql
     * @param array $params
     */
    private function  __construct($sql, array $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return SQLPrepared
     */
    public static function getInstance($sql, array $params)
    {
        return new self($sql, $params);
    }

    /**
     * @return string
     */
    public function execute()
    {
        $results = [];
        preg_match_all('/\%(' . implode('|', self::$replacements) . ')/', $this->sql, $results);
        $results = $results[1];

        foreach ($this->params as $k => &$v) {
            if (!isset($results[$k])) {
                trigger_error('Incorrect parameter count');
            }

            switch ($results[$k]) {
                case 's':
                case 'string':
                    $v = '"' . sql_prepare($v) . '"';
                    break;
                case 'i':
                case 'int':
                    $v = (int)$v;
                    break;
                case 'ui':
                case 'uint':
                    $v = abs((int)$v);
                    break;
                case 'f':
                case 'float':
                    $v = (float)$v;
                    break;
                case 'l':
                case 'like':
                    $v = '"' . sql_prepare($v) . '"';
                    break;
                case 'ai':
                case 'arrayint':
                    foreach ($v as &$unit) {
                        $unit = (int)$unit;
                    }
                    $v = implode(',', $v);
                    unset($unit);
                    break;
                case 'aui':
                case 'arrayuint':
                    dump($v);
                    if (!is_array($v)) trigger_error('Parameter "' . ($k + 1) . '" must be an array becouse query contains "%' . $results[$k] . '".');
                    foreach ($v as &$unit) {
                        $unit = abs((int)$unit);
                    }
                    $v = implode(',', $v);
                    unset($unit);
                    break;
                case 'as':
                case 'arraystring':
                    if (!is_array($v)) trigger_error('Parameter "' . ($k + 1) . '" must be an array becouse query contains "%' . $results[$k] . '".');
                    foreach ($v as &$unit) {
                        $unit = '"' . sql_prepare($unit) . '"';
                    }
                    $v = implode(',', $v);
                    unset($unit);
                    break;
                case 'af':
                case 'arrayfloat':
                    if (!is_array($v)) trigger_error('Parameter "' . ($k + 1) . '" must be an array becouse query contains "%' . $results[$k] . '".');
                    foreach ($v as &$unit) {
                        $unit = (float)$unit;
                    }
                    $v = implode(',', $v);
                    unset($unit);
                    break;
                case 'auf':
                case 'arrayufloat':
                    if (!is_array($v)) trigger_error('Parameter "' . ($k + 1) . '" must be an array becouse query contains "%' . $results[$k] . '".');
                    foreach ($v as &$unit) {
                        $unit = abs((float)$unit);
                    }
                    $v = implode(',', $v);
                    unset($unit);
                    break;
                default:
                    trigger_error('Unknown Parameter');
            }
        }
        unset($v);

        $tmp = [];
        foreach (self::$replacements as $v) {
            $tmp[] = '%' . $v;
        }

        return vsprintf(str_replace($tmp, '%s', $this->sql), $this->params);
    }
}