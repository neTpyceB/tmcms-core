<?php

namespace neTpyceB\TMCms\Admin;

use neTpyceB\TMCms\Admin\Structure\Object\LanguageCollection;

defined('INC') or exit;

/**
 * Languages for admin panel
 *
 * Class AdminLanguages
 * @package neTpyceB\TMCms\Admin
 */
class AdminLanguages
{
    /**
     * Available languages
     * @return array
     */
    public static function getPairs()
    {
        $default_languages = [
            'en' => 'English'
        ];

        $languages_from_site = new LanguageCollection();
        $default_languages = array_merge($default_languages, $languages_from_site->getPairs('full', 'short'));

        return $default_languages;
    }
}