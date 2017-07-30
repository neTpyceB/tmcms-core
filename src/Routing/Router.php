<?php

namespace TMCms\Routing;

use TMCms\Admin\Structure\Entity\PageAliasEntity;
use TMCms\Admin\Structure\Entity\PageEntityRepository;
use TMCms\Admin\Structure\Entity\PageAliasEntityRepository;
use TMCms\Admin\Structure\Entity\PageTemplateEntityRepository;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpCountryEntityRepository;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntity;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntityRepository;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\Files\FileSystem;
use TMCms\Files\Finder;
use TMCms\Network\Domains;
use TMCms\Network\SearchEngines;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class Router
 */
class Router
{
    use singletonOnlyInstanceTrait;

    /**
     * @var array
     */
    private $path;
    /**
     * @var array
     */
    private $get;
    /**
     * @var array
     */
    private $page;

    /**
     * Router constructor.
     */
    private function __construct()
    {
        /* Parse URL */
        $parse_url = SELF;
        if ($parse_url === '/index.php') {
            $parse_url = $_SERVER['REQUEST_URI'];
        }

        $path = [];
        if ((!$url = parse_url($parse_url)) || !isset($url['path'])) {
            die('URL can not be parsed');
        }

        // Remove empty parts
        foreach (explode('/', $url['path']) as $pa) {
            if ($pa) {
                $path[] = $pa;
            }
        }

        // For non-rewrite hosting servers
        if (end($path) === 'index.php') {
            array_pop($path);
        }

        $_path_original = $path;

        define('PATH_SO', count($path));
        define('PATH_ORIGINAL', ($path ? '/' . implode('/', $path) : '') . '/');

        /* In case user came from search engine */
        define('REF_DOMAIN', REF ? Domains::get(REF) : '');
        define('REF_DOMAIN_NAME', REF ? Domains::getName(REF) : '');
        define('REF_SE_KEYWORD', REF ? (REF_DOMAIN === CFG_DOMAIN ? '' : SearchEngines::getSearchWord(REF)) : '');

        /* Page aliases */
        if (Settings::get('page_aliases_enabled')) {
            $page_alias = NULL;

            // If client comes from search engine - check which page is more appropriate for hom
            if (REF_SE_KEYWORD && CFG_DOMAIN !== REF_DOMAIN) {
                $similarities = [];

                $page_aliases = new PageAliasEntityRepository();
                $page_aliases->setWhereIsLanding(1);

                // Sort by similarity
                foreach ($page_aliases->getAsArrayOfObjectData() as $page_alias) {
                    similar_text(REF_SE_KEYWORD, $page_alias['name'], $match);
                    if ($match >= REF_SE_KEYWORD_MIN_MATCH && !isset($similarities[$match])) {
                        $similarities[$match] = $page_alias['name'];
                    }
                }

                if ($similarities) {
                    ksort($similarities);

                    $quick_link_name = current($similarities);

                    // Try cache
                    $cache_key = 'page_aliases_' . $quick_link_name;
                    $page_alias = NULL;
                    if (Settings::isCacheEnabled()) {
                        $page_alias = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
                    }

                    // Get from db
                    if ($page_alias === NULL) {
                        $page_aliases = new PageAliasEntityRepository();
                        $page_aliases->setWhereName($quick_link_name);

                        $page_alias = $page_aliases->getFirstObjectFromCollection();

                        // Save in cache
                        if (Settings::isCacheEnabled()) {
                            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $page_alias);
                        }
                    }
                }
            }

            // In case we have only one key in path and have not landing page redirect
            if (PATH_SO === 1 && !$page_alias) {
                $cache_key = 'page_aliases_' . $path[0];
                $page_alias = NULL;

                // Find in cache
                if (Settings::isCacheEnabled()) {
                    $page_alias = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
                }

                // Find in db
                if ($page_alias === NULL) {
                    $page_aliases = new PageAliasEntityRepository();
                    $page_aliases->addSimpleSelectFields(['page_id', 'href']);
                    $page_aliases->setWhereName($path[0]);

                    // If came from search engine
                    if (REF_SE_KEYWORD) {
                        $page_aliases->setWhereName(REF_SE_KEYWORD);
                    }

                    $page_alias = $page_aliases->getFirstObjectFromCollection();
                }

                // Save alis in cache
                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()->getDefaultCacher()->set($cache_key, (string)$page_alias);
                }
            }

            // If page alias found - redirect
            /** @var PageAliasEntity $page_alias */
            if ($page_alias) {
                // Check by url string or by page id saved in alias
                if (!Structure::pageExists(Structure::getIdByPath($page_alias->getHref()))) {
                    $page_alias->setHref(Structure::getPathById($page_alias->getPageId()));
                }

                // Redirect only if page exists
                if ($page_alias->getHref()) {
                    go($page_alias->getHref());
                }
            }

        }

        //=== Deal With languages

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

        // Language from HTTP header
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

        define('PATH', '/' . implode('/', $path) . ($path ? '/' : ''));

        //=== Deal with Structure pages

        // API pages
        // Format for example = "/LNG/api/file/action/id/?some_params=1&other_params=ok"
        if (isset($path[1], $path[2]) && $path[1] === CFG_API_ROUTE) {
            $api_request = $path[2];
            if (!FileSystem::checkFileName($api_request)) {
                if (Settings::isProductionState()) {
                    return;
                }
                dump('Requested api action contains invalid characters.');
            }

            // Search file in library...
            $api_file = Finder::getInstance()->searchForRealPath($api_request . '.php', Finder::TYPE_API);
            if (!is_file(DIR_BASE . $api_file)) {
                if (Settings::isProductionState()) {
                    return;
                }
                dump('Requested ajax action does not exist. Searched for "' . $api_file . '"');
            };

            define('API_FILE_NAME', $path[2]);

            // Action for REST services
            $api_rest_action = '';
            if (isset($path[3])) {
                $api_rest_action = $path[3];
            }
            define('API_ACTION', $api_rest_action);

            $ajax_rest_id = '';
            if (isset($path[4])) {
                $ajax_rest_id = $path[4];
            }
            define('API_ID', $ajax_rest_id);
            define('PAGE_ID', NULL);

            require_once DIR_BASE . $api_file;

            die;
        }

        // We did not find any parts in ULR to build path from - prepend current Language for correct work
        if (!$path) {
            $path[] = LNG;
        }

        // Add invisible language part in inner URL if page exists under current language
        if (isset($path[0]) && $path[0] != LNG/* && Structure::getIdByPath('/'. LNG .'/'. implode('/', $path) . '/')*/) {
            array_unshift($path, LNG);
        }

        // Build path
        $tmp = $path_pairs = [];
        $q['id'] = 0;
        $count_of_parts_in_path = count($path);

        // We can have existing page match even under the transparent get params
        $is_transparent = false;

        for ($i = 0; $i < $count_of_parts_in_path; $i++) {
            $cache_key = 'cms_router_' . md5($i . $path[$i] . '_' . $q['id']);
            $cached_q = NULL;

            if (Settings::isCacheEnabled()) {
                $cached_q = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            }

            if ($cached_q === NULL) {
                $pages = new PageEntityRepository();
                $pages->addSimpleSelectFields(['id', 'string_label', 'location', 'title', 'transparent_get', 'go_level_down']);
                $pages->setWhereLocation($path[$i]);
                $pages->setWherePid($q['id']);
                $pages->setWhereActive(true);
                $page = $pages->getFirstObjectFromCollection();

                if ($page) {
                    $q = $page->getAsArray();
                } else {
                    if ($is_transparent || Settings::get('error_404_convert_transparent_get')) {
                        break;
                    }

                    $q = NULL;
                }
            } else {
                $q = $cached_q;
            }

            if (!$q) {
                $path_of_404 = Structure::pageNotFound($tmp, $path[$i]);

                if ($path_of_404) {
                    // Search for error 404 page

                    $q = NULL;
                    $pages = new PageEntityRepository();
                    $pages->addSimpleSelectFields(['id', 'location', 'title', 'transparent_get', 'go_level_down']);
                    $pages->setWhereId(Structure::getIdByPath($path_of_404));
                    $pages->setWhereActive(true);

                    $page = $pages->getFirstObjectFromCollection();
                    if ($page) {
                        $q = $page->getAsArray();
                    }

                    $exploded_404 = explode('/', $path_of_404);
                    $parts_404 = [];

                    foreach ($exploded_404 as $part_404) {
                        if (!$part_404) {
                            continue;
                        }
                        $parts_404[] = $part_404;
                    }

                    $tmp = $path_pairs = $parts_404;

                    break;
                }
            } else {
                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()->getDefaultCacher()->set($cache_key, $q);
                }

                $tmp[] = $q;
                $path_pairs[] = $q['location']; // $q['location'] == $path[$i];

                // If other params in URL are just params but not pages in tree
                $is_transparent = false;
                if ($q['transparent_get']) {
                    $is_transparent = true;
                }
            }
        }

        // Check if we have language part in link and whether we need to skip it
        if (Settings::get('skip_lng_redirect_to_same_page') && isset($_path_original[0]) && LNG == $_path_original[0]) {
            $path_to_cut = $_path_original;
            array_shift($path_to_cut);
            $path_to_cut = ($path_to_cut ? '/' . implode('/', $path_to_cut) : '') . '/';
            go($path_to_cut);
        }

        $this->path = $tmp; // Save path
        define('PAGE_IS_MAIN', count($this->path) < 2); // Is main page

        // Language must be in path always
        if (!isset($path_pairs[0]) || !$path_pairs[0]) {
            $path_pairs[0] = LNG;
        }

        // Internal address is path to real CMS page - may vary from real URL if virtual internal redirects are enabled
        $internal_path = '/' . implode('/', $path_pairs) . ($path_pairs ? '/' : '');

        // Check if need to go to the first child
        if ($count_of_parts_in_path && $q['go_level_down']) {
            $go_down_cache_key = 'go_level_down' . $internal_path;
            // Check cache
            $cached_q = NULL;
            if (Settings::isCacheEnabled()) {
                $cached_q = Cacher::getInstance()->getDefaultCacher()->get($go_down_cache_key);
            }

            if ($cached_q === NULL) {
                // Not in cache - find last in queue
                while ($q['go_level_down']) {
                    $tmp = NULL;
                    $pages = new PageEntityRepository();
                    $pages->addSimpleSelectFields(['id', 'go_level_down']);
                    $pages->setWherePid($q['id']);
                    $pages->setWhereActive(true);
                    $pages->addOrderByField('order');

                    $page = $pages->getFirstObjectFromCollection();
                    if ($page) {
                        $tmp = $page->getAsArray();
                    }

                    if ($tmp) {
                        $q = $tmp;
                    } else {
                        break;
                    }
                }
                // Save in cache
                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()->getDefaultCacher()->set($go_down_cache_key, $q);
                }
            } else {
                // Set from cache
                $q = $cached_q;
            }

            // Rewrite internal path ro real page in CMS - may be not the same as URL
            $internal_path = Structure::getPathById($q['id'], false, true);
        }

        define('PATH_INTERNAL', $internal_path);
        define('PATH_INTERNAL_MD5', md5($internal_path));

        // Build GET parameters from excess params
        for($i=count($path_pairs); $i<$count_of_parts_in_path; $i++){
            $_GET[] = $path[$i];
        }

        $this->get = $_GET;


        //=== Page data

        $q = [];
        $cached_q = NULL;
        $cache_key = 'cms_pages_page' . PATH_INTERNAL_MD5;

        // Check cache
        if (Settings::isCacheEnabled()) {
            $cached_q = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if (is_array($cached_q)) {
                $q = $cached_q;
            }
        }

        // Get page
        if (!$q) {
            $q = NULL;
            $pages = new PageEntityRepository();

            $pages->addSimpleSelectFields(['id', 'pid', 'string_label', 'location', 'title', 'browser_title', 'keywords', 'description', 'redirect_url', 'html_file']);
            $pages->setWhereId(Structure::getIdByPath($internal_path));
            $pages->setWhereActive(true);

            $templates = new PageTemplateEntityRepository();
            $templates->addSimpleSelectFields(['file']);

            $pages->mergeWithCollection($templates, 'template_id');

            $page = $pages->getFirstObjectFromCollection();
            if ($page) {
                $q = $page->getAsArray();
            }

            if ($q) {
                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()->getDefaultCacher()->set($cache_key, $q);
                }
            } else {
                dump('Page not found in DB');
            }
        }

        // Redirect
        if ($q['redirect_url']) {
            // If have page_id in url
            if (ctype_digit((string)$q['redirect_url'])) {
                $q['redirect_url'] = Structure::getPathById($q['redirect_url']);
            }

            go($q['redirect_url']);
        }

        $q['template_file'] = DIR_FRONT_TEMPLATES . $q['file']; // Cache page if it is set in page properties or in global Settings

        define('PAGE_ID', (int)$q['id']);

        $this->page = $q;
    }

    /**
     * @return string
     */
    public function getCurrentPathAsString()
    {
        $res = [];
        foreach ($this->getPath() as $v) {
            $res[] = $v['location'];
        }

        return '/' . implode('/', $res) . '/';
    }

    /**
     * @return array
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getGetParams()
    {
        return $this->get;
    }

    /**
     * @return array
     */
    public function getPageData()
    {
        return $this->page;
    }
}