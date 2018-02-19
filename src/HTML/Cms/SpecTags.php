<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

/**
 * Class SpecTags
 * @package TMCms\HTML\Cms
 */
class SpecTags {
    private static $default_tags = [
        'script',
        'iframe',
        'frameset',
        'frame',
        'embed',
        'object'
    ];

    private static $black_list = [];
    private static $white_list = [];

    private static $use_black_list = false;
    private static $use_white_list = false;

    /**
     * @param string $value
     *
     * @return string
     */
    public static function escape(string $value): string
    {
        if (self::$use_white_list) {
            $preg = '/<(\s*\/?\s*[^(?:'. implode(',', self::$white_list) .')]\s*)>/i';
        } elseif (self::$use_black_list) {
            $preg = '/<(\s*\/?\s*(?:'.  implode(',', self::$black_list) .')\s*)>/i';
        } else {
            $preg = '/<(\s*\/?\s*(?:'. implode(',', self::$default_tags) .')\s*)>/i';
        }

        $value = preg_replace($preg, '&lt;$1&gt;', $value);

        return $value;
    }

    /**
     * @param array $tags
     */
    public static function addBlackList(array $tags) {
        self::$use_black_list = true;
        self::$use_white_list = false;
        self::$black_list = $tags;
    }

    /**
     * @param array $tags
     */
    public static function addWhiteList(array $tags) {
        self::$use_black_list = false;
        self::$use_white_list = true;
        self::$white_list = $tags;
    }
}
