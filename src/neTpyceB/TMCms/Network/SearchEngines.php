<?php

namespace neTpyceB\TMCms\Network;

defined('INC') or exit;

/**
 * Class SearchEngines
 * @package neTpyceB\TMCms\Network
 */
class SearchEngines
{

    /**
     * List of seach engines => search query key(s)
     * @var array
     */
    private static $se = [
        'aol' => ['query', 'encquery', 'q'],
        'szukaj' => ['szukaj', 'qt'],
        'bing' => 'q',
        'google' => 'q',
        'yahoo' => 'p',
        'live' => 'q',
        'msn' => 'q',
        'lycos' => 'query',
        'ask' => 'q',
        'altavista' => 'q',
        'netscape' => 'query',
        'cnn' => 'query',
        'looksmart' => 'qt',
        'about' => 'terms',
        'mamma' => 'query',
        'alltheweb' => 'q',
        'gigablast' => 'q',
        'voila' => 'rdata',
        'virgilio' => 'qs',
        'baidu' => 'wd',
        'alice' => 'qs',
        'yandex' => 'text',
        'najdi' => 'q',
        'club-internet' => 'q',
        'mama' => 'query',
        'seznam' => 'q',
        'search' => 'q',
        'netsprint' => 'q',
        'szukacz' => 'q',
        'yam' => 'k',
        'pchome' => 'q',
        'mail.ru' => 'q',
        'rambler.ru' => 'words',
        'meta.ua' => 'q',
        'bigmir.net' => 'q',
        'aport.ru' => 'r',
        'a-counter' => 'sub_data',
        'i.ua' => 'q'
    ];

    /**
     * Get search engine name by query string. required for SEO leads
     * @example $url = http://www.subsub.subdomain.google.lv/search?sourceid=chrome&ie=UTF-8&q=dfgdfgb+bgfhgfn+gfgfh+g
     * @param string $url
     * @return bool
     */
    public static function getSearchWord($url)
    {
        $parsed_url = parse_url($url);
        if (!isset($parsed_url['host'])) {
            $parsed_url['host'] = '';
        }

        // Have domain
        $domain_parts = array_reverse(explode('.', $parsed_url['host']));
        if (!isset($domain_parts[1])) {
            return false;
        }

        // Known search engine
        $engine = $domain_parts[1];
        if (!isset(self::$se[$engine])) {
            return false;
        }

        // Have search query
        $search_params = self::$se[$engine];
        if (!isset($parsed_url['query'])) {
            return false;
        }

        // Get query
        parse_str($parsed_url['query'], $params);
        if (!$params) {
            return false;
        }

        // Check engine's matches
        if (is_string($search_params)) {
            // If string key
            return isset($params[self::$se[$engine]]) ? $params[self::$se[$engine]] : false;
        }

        // If multiple keys possible
        foreach ($search_params as $v) {
            if (!isset($params[$v])) continue;
            return $params[$v];
        }

        return false;
    }
}