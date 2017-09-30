<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

defined('INC') or exit;

/**
 * Class TableLinker
 */
class Linker
{
    public $table_id;

    private $p;
    private $do;

    /**
     * @param string $p
     * @param string $do
     */
    public function __construct(string $p = P, string $do = '')
    {
        $this->p = $p;

        if ($do) {
            $this->do = $do;
        }
    }

    /**
     * Return url, if key from parameter exists - add parameter value to url
     *
     * @param array $params
     *
     * @return string
     */
    public static function makeUrl(array $params): string
    {
        $href = [];

        foreach ($params as $key => $value) {
            $href[] = static::makeParam($key, (string)$value);
        }

        return '?' . implode('&', $href);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getHrefWithDoAppend(array $params): string
    {
        $href = [];
        $href[] = static::makeParam('p', $this->p);
        $href[] = $this->parseParameter('do', $this->do == '_default' ? '' : $this->do, $params);

        foreach ($params as $key => $value) {
            $href[] = static::makeParam($key, $value);
        }

        return '?' . implode('&', $href);
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    private static function makeParam(string $key, string $value): string
    {
        if (is_array($value)) {
            $tmp = [];
            foreach ($value as $v) {
                $tmp[] = urlencode($key) . '[]=' . urlencode($v);
            }

            return implode('&', $tmp);
        }

        return urlencode($key) . '=' . urlencode($value);
    }

    /**
     * @param string $key
     * @param string $value
     * @param array  $params reference
     *
     * @return string
     */
    private function parseParameter(string $key, string $value, &$params): string
    {
        if (isset($params[$key])) {
            $par = $params[$key];
            unset($params[$key]);

            $value = $value ? $value . '_' . $par : $par;
        }

        return static::makeParam($key, $value);
    }
}