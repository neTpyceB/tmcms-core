<?php
declare(strict_types=1);

use TMCms\Config\Settings;
use TMCms\Routing\Interfaces\IMiddleware;
use TMCms\Routing\Languages;

class LanguagesMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        $path = explode('/', PATH);

        /* Get language */
        $languages = Languages::getPairs();
        $lng = false;

        if (!$languages) {
            die('No any language found in system.');
        }

        // Language from path
        if (isset($path[0], $languages[$path[0]])) {
            $lng = $path[0];
        }

        // Language from previous visit in browser
        if (!$lng && Settings::get('lng_by_session') && isset($_SESSION['language'], $languages[$_SESSION['language']])) {
            $lng = $_SESSION['language'];
        }

        // Language from cookie
        if (!$lng && Settings::get('lng_by_cookie') && isset($_COOKIE['language'], $languages[$_COOKIE['language']]) && Languages::exists($_COOKIE['language'])) {
            $lng = $_COOKIE['language'];
        }

        // Language from HTP header
        if (!$lng && Settings::get('lng_by_http_header') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $v) {
                if (isset($v[0], $v[1])) {
                    $lng_k = $v[0] . $v[1];
                    if (isset($languages[$lng_k])) {
                        $lng = $lng_k;
                        break;
                    }
                }
            }
        }

        // Language from Settings
        if (!$lng) {
            $tmp = Settings::get('f_default_language');
            if (isset($languages[$tmp])) {
                $lng = $tmp;
            }
        }

        // Language as first from the list
        if (!$lng && $languages) {
            $lng = key($languages);
        }

        // If no language so far
        if (!$lng || (!$lng = Languages::getLanguageDataByShort($lng))) {
            die('Can not recognize language');
        }

        // Set language data
        define('LNG', $lng['short']);

        // Save in session
        if (Settings::get('lng_by_session')) {
            $_SESSION['language'] = LNG;
        }
        // Save in cookies
        if (Settings::get('lng_by_cookie') && !headers_sent()) {
            setcookie('language', LNG, NOW + 2592000, '/');
        }
    }
}