<?php
declare(strict_types=1);

use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntity;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntityRepository;
use TMCms\Config\Settings;
use TMCms\Routing\Interfaces\IMiddleware;
use TMCms\Routing\Languages;

class LanguagesMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        $path = PATH_ROUTER;

        /* Get language */
        $languages = Languages::getPairs();
        $lng = '';

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

        // Default visitor country is the selected language
        $visitor_country_code = $lng;

        // Get country by range
        $ranges = new MaxMindGeoIpRangeEntityRepository();
        $ranges->enableUsingCache(3600);
        $ranges->addSimpleSelectFields(['country_code']);
        $ranges->addWhereFieldIsHigherOrEqual('start', IP_LONG);
        $ranges->addWhereFieldIsLowerOrEqual('end', IP_LONG);
        $range = $ranges->getFirstObjectFromCollection();
        /** @var MaxMindGeoIpRangeEntity $range */
        if ($range) {
            $visitor_country_code = $range->getCode();
        }
        define('VISITOR_COUNTRY_CODE', strtolower($visitor_country_code));

        // Language by client's IP
        if (!$lng && VISITOR_COUNTRY_CODE && Settings::get('lng_by_ip') & isset($languages[VISITOR_COUNTRY_CODE])) {
            $lng = VISITOR_COUNTRY_CODE;
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