<?php

namespace TMCms\Routing;

use function dump;
use RuntimeException;
use TMCms\Admin\Structure\Entity\PageComponentEntity;
use TMCms\Admin\Structure\Entity\PageComponentHistory;
use TMCms\Admin\Structure\Entity\PageComponentHistoryRepository;
use TMCms\Admin\Structure\Entity\PageComponentEntityRepository;
use TMCms\Admin\Structure\Entity\PageRedirectHistoryEntity;
use TMCms\Admin\Structure\Entity\PageRedirectHistoryEntityRepository;
use TMCms\Admin\Structure\Entity\PageTemplateEntity;
use TMCms\Admin\Structure\Entity\PageTemplateEntityRepository;
use TMCms\Admin\Structure\Entity\PageEntity;
use TMCms\Admin\Structure\Entity\PageEntityRepository;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\DB\TableTree;
use TMCms\Files\FileSystem;
use TMCms\Log\FrontendLogger;
use TMCms\Network\PageCrawler;
use TMCms\Routing\Entity\PageComponentsCachedEntityRepository;
use TMCms\Routing\Entity\PageComponentsDisabledEntityRepository;
use TMCms\Routing\Entity\PagesWordEntity;
use TMCms\Routing\Entity\PagesWordEntityRepository;
use TMCms\Templates\VisualEdit;

defined('INC') or exit;

/**
 * Class Structure
 * @package TMCms\Routing
 */
class Structure
{
    private static $_path_cache = [];
    private static $_labelCache = [];
    private static $_page_data_cache = [];
    private static $_words_cache;

    /**
     * @param $label
     * @param string $lng
     * @param bool $with_domain
     * @return string
     */
    public static function getPathByLabel($label, $lng = LNG, $with_domain = true)
    {
        return self::getPathById(self::getIdByLabel($label, $lng), $with_domain);
    }

    /**
     * Return path of page based on ID
     * @param int  $page_id
     * @param bool $with_domain
     * @param bool $disallow_cut_language_part in case you need to keep /xx/ language part even if it is enabled in Settings
     * @return string
     */
    public static function getPathById($page_id, $with_domain = true, $disallow_cut_language_part = false)
    {
        // Page id must be integer
        if (!ctype_digit((string)$page_id)) {
            return false;
        }

        // Already scanned
        if (isset(self::$_path_cache[$page_id])) {
            return ($with_domain && substr(self::$_path_cache[$page_id], 0, 1)=='/' ? BASE_URL : '') . self::$_path_cache[$page_id];
        }
        // Check common cache
        $cache_key = 'structure_path_by_id_' . $page_id;
        if (Settings::isCacheEnabled()) {
            self::$_path_cache[$page_id] = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if (self::$_path_cache[$page_id]) {
                return ($with_domain ? BASE_URL : '') . self::$_path_cache[$page_id];
            }
        }
        $path = '';

        $page_entity = new PageEntity();
        $page_entity->setPid($page_id);

        // Look through all parts to find last page
        while ($page_entity && $page_entity->getPid()) {

            $page_entity_collection = new PageEntityRepository();
            $page_entity_collection->setWhereId($page_entity->getPid());

            // Only active pages in front site
            if (MODE === 'site') {
                $page_entity_collection->setWhereActive(1);
            }

            /** @var PageEntity $page_entity */
            $page_entity = $page_entity_collection->getFirstObjectFromCollection();
            if ($page_entity) {
                $path = $page_entity->getLocation() . '/' . $path;
            }
        }

        if (!$path) {
            return false;
        }

        // Add slash to open page from absolute path
        $path = '/' . $path;

        // Remove first language part from path if it is allowed and enabled
        if (!$disallow_cut_language_part && Settings::get('skip_lng_in_generated_links')) {
            // Cut "/xx" part
            $path = substr($path, 3);
        }

        // Add domain in links only if required
        if ($with_domain) {
            $path = BASE_URL . $path;
        }

        // Save common cache
        if (Settings::isCacheEnabled()) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $path);
        }

        return self::$_path_cache[$page_id] = $path;
    }

    /**
     * @param string $label
     * @param string $lng
     * @return int
     */
    public static function getIdByLabel($label, $lng = LNG)
    {
        $full_label = $lng . $label;

        if (isset(self::$_labelCache[$full_label])) {
            return self::$_labelCache[$full_label];
        }

        $cache_key = 'structure_id_by_label_' . $full_label;
        if (Settings::isCacheEnabled()) {
            self::$_labelCache[$full_label] = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if (self::$_labelCache[$full_label]) {
                return self::$_labelCache[$full_label];
            }
        }


        // Get paths for every language with this label
        $lng_id = Languages::getIdByShort($lng);
        $data = [];

        $page_entity_collection = new PageEntityRepository();
        $page_entity_collection->setWhereStringLabel($label);

        // Only active in front site
        if (MODE === 'site') {
            $page_entity_collection->setWhereActive(1);
        }

        // Get data fro required label
        foreach ($page_entity_collection->getPairs('id') as $v) {
            $data[Languages::getIdByPageId($v)] = $v;
        }

        if (isset($data[$lng_id])) {
            self::$_labelCache[$full_label] = $data[$lng_id];

            // Save in common cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()->set($cache_key, self::$_labelCache[$full_label], 86400);
            }
        }

        return isset(self::$_labelCache[$full_label]) ? self::$_labelCache[$full_label] : 0;
    }

    /**
     * @param array $path parsed if need to guess
     * @param bool $broken path that is wrong
     * @return string
     */
    public static function pageNotFound($path = NULL, $broken = true)
    {
        // Look for location change
        if (Settings::get('save_location_change_history')) {

            // Check if page location ever existed
            $page_entity = new PageRedirectHistoryEntityRepository();
            $page_entity->setWhereOldFullUrl(PATH_ORIGINAL);
            $page_entity->addOrderByField('id', true);

            $page_entity = $page_entity->getFirstObjectFromCollection();

            /** @var PageRedirectHistoryEntity $page_entity */
            // Redirect
            if ($page_entity) {
                header('HTTP/1.1 301 Moved Permanently');
                go($page_entity->getNewFullUrl());
            }
            if(QUERY){
                // Check if page location ever existed
                /** @var PageRedirectHistoryEntityRepository $page_entity */
                $page_entity = new PageRedirectHistoryEntityRepository();
                $page_entity->setWhereOldFullUrl(PATH_ORIGINAL.'?'.QUERY);
                $page_entity->addOrderByField('id', true);

                $page_entity = $page_entity->getFirstObjectFromCollection();

                /** @var PageRedirectHistoryEntity $page_entity */
                // Redirect
                if ($page_entity) {
                    header('HTTP/1.1 301 Moved Permanently');
                    go($page_entity->getNewFullUrl());
                }
            }
        }

        // Try to guess and repair path and redirect to another page
        if (Settings::get('guess_broken_path')) {
            self::guessBrokenPath($path, $broken);
        }

        // Try to find page 404 in structure for this language
        if (Settings::get('error_404_find_in_structure') && Structure::pageExists(Structure::getIdByPath('/' . LNG . '/404/'))) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            return '/' . LNG . '/404/';
        }

        // Go to main page
        if (Settings::get('error_404_go_to_main')) {
            go('/' . LNG . '/');
        }

        // Try to go to exact 404 page
        if (Settings::get('error_404_page') && Structure::pageExists(Structure::getIdByPath(Settings::get('error_404_page')))) {
            go(Settings::get('error_404_page'));
        }

        // Try to show default 404 page
        if (Settings::get('error_404_show_default_page')) {
            self::showDefault404();
        }

        // Show status 404 if nothing worked till here
        @ob_clean();
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        echo 'Error 404. Page not found.';

        exit;
    }

    /**
     * Guess nearest possible path if page does not exist, instead of error 404
     * @param array $p - last normal page array (one level up)
     * @param string $broken - path that was not found
     * @return bool
     */
    private static function guessBrokenPath($p, $broken)
    {
        // Make location url
        $tmp = [];
        foreach ($p as $v) {
            $tmp[] = $v['location'];
        }
        $p = $tmp;

        $page_entity_collection = new PageEntityRepository();
        $page_entity_collection->setWherePid((int)self::getIdByPath(implode('/', $p)));
        $page_entity_collection->setWhereActive(1);

        // Check all pages and find most appropriate
        $possibilities = [];
        foreach ($page_entity_collection->getPairs('location', 'id') as $k => $v) {
            $res = levenshtein($v, $broken);
            $possibilities[$k] = $res;
        }
        natsort($possibilities);

        if ($possibilities) {
            go(self::getPathById(key($possibilities)));
        }

        return false;
    }

    /**
     * Returns ID of page based on path
     * @param string $path
     * @return int
     */
    public static function getIdByPath($path)
    {
        $path = trim($path);

        // Already scanned
        if (isset(self::$_path_cache[$path])) {
            return self::$_path_cache[$path];
        }

        // Page can have internal redirect to show another page
        $hash = defined('PATH_INTERNAL_MD5') ? PATH_INTERNAL_MD5 : md5($path);

        // Check common cache
        $cache_key = 'structure_id_by_path_' . $hash;
        if (Settings::isCacheEnabled()) {
            self::$_path_cache[$path] = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if (self::$_path_cache[$path]) {
                return self::$_path_cache[$path];
            }
        }

        // Parse url to chunks and remove empty parts
        $temp_array = explode('/', $path);
        $temp = [];
        foreach ($temp_array as $p) {
            if ($p != '') {
                $temp[] = $p;
            }
        }

        $page_entity = new PageEntity();
        $last_page_entity = NULL;

        // Look in database for real page to show
        $i = 0;
        while (isset($temp[$i]) && $page_entity) {
            // Find pid page
            $page_entity_collection = new PageEntityRepository();
            $page_entity_collection->setWhereLocation($temp[$i]);
            $page_entity_collection->setWherePid($page_entity->getId());

            // Show only active pages in front site
            if (MODE === 'site') {
                $page_entity_collection->setWhereActive(1);
            }

            // This tells us that we reached end of parts
            $page_entity = $page_entity_collection->getFirstObjectFromCollection();
            if ($page_entity) {
                $last_page_entity = $page_entity;
            }

            ++$i;
        }

        // Save to cache
        if (Settings::isCacheEnabled() && $last_page_entity) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $last_page_entity->getId());
        }

        return self::$_path_cache[$path] = $page_entity ? $page_entity->getId() : NULL;
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function pageExists($id)
    {
        $page_entity_collection = new PageEntityRepository();
        $page_entity_collection->setWhereId($id);

        // Only active pages for front site
        if (MODE === 'site') {
            $page_entity_collection->setWhereActive(1);
        }

        return $page_entity_collection->hasAnyObjectInCollection();
    }

    private static function showDefault404()
    {
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');

        $file_404 = DIR_FRONT_TEMPLATES . '404.html';

        if (!file_exists($file_404)) {
            return false;
        }

        require_once $file_404;

        exit;
    }

    /**
     * @param string $k key of word
     * @param string $lng selected language
     * @param array $replaces params like ['name' => 'John', 'age' => 18]
     * @param string $default default text to render if key is not set or empty
     * @param bool $no_cache do not use cache for dupe calls
     * @return string
     */
    public static function getWord($k, $lng = LNG, $replaces = [], $default = '', $no_cache = false)
    {
        if (!$lng) {
            $lng = LNG;
        }
        if (!$replaces) {
            $replaces = [];
        }

        $languages = Languages::getPairs();
        if (!isset($languages[$lng])) {
            return '';
        }

        $temp = $k . '_' . $lng;

        if (!isset(self::$_words_cache[$temp])) {
            self::cacheAllWords($lng);
        }

        if (!empty($replaces)) {
            $temp_key = $temp . '@@' . serialize($replaces);
        } else {
            $temp_key = $temp;
        }

        if (!$no_cache && isset(self::$_words_cache[$temp_key])&&self::$_words_cache[$temp_key]) { // Do not cache if forced
            return self::$_words_cache[$temp_key];
        }

        $cache_key = 'variable_word_' . $temp_key;

        $q = NULL;

        // Get from cache
        if (Settings::isCacheEnabled()) {
            $q = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if ($q !== NULL) {
                return self::$_words_cache[$temp_key] = $q;
            }
        }

        if (!$no_cache && !empty($replaces) && isset(self::$_words_cache[$temp])) {
            $q = self::$_words_cache[$temp];
        } else {
            // Get from DB
            /** @var PagesWordEntity $word_obj */
            $word_obj = PagesWordEntityRepository::findOneEntityByCriteria([
                'name' => $temp
            ]);
            $q = $word_obj ? $word_obj->getWord() : false;
        }

        // Add if non exists
        if ($q === false && !Settings::isProductionState()) {
            self::addWord(['name' => $k]);
        }

        // If word is empty, we can show place for it
        if (!$q) {
            $q = $k;
        }

        // Replace variables
        foreach ($replaces as $k => $v) {
            $q = str_replace('{%' . $k . '%}', $v, $q);
        }

        // Found word - save to local cache
        if ($q . '_' . $lng != $temp) {
            self::$_words_cache[$temp_key] = $q;
        } elseif ($default) { // Have default value - should save it in db
            $main_lng = Settings::get('f_default_language');
            $checked_word = $k . '_' . $main_lng;

            /** @var PagesWordEntity $checked_word_obj */
            $checked_word_obj = PagesWordEntityRepository::findOneEntityByCriteria([
                'name' => $checked_word
            ]);

            // Found entry and no data set yet
            if ($checked_word_obj && !$checked_word_obj->getWord()) {
                $checked_word_obj->setWord($default);
                $checked_word_obj->save();
            }
        }

        // Save in cache
        if (Settings::isCacheEnabled()) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $q);
        }

        // Border to show editable placeholder
        if ($q !== false && VisualEdit::getInstance()->isEnabled()) {
            $q = VisualEdit::getInstance()->wrapAroundComponents('', $temp, $q, 'word');
        }

        // Nothing found - show default text
        if ($default && $q == $k) {
            return $default;
        }

        return $q;
    }

    public static function cacheAllWords($lng)
    {
        $words = PagesWordEntityRepository::getInstance()->addWhereFieldIsLike('name', "_" . $lng, true, false)->getAsArrayOfObjects();
        /* @var PagesWordEntity $w */
        foreach ($words as $w) {
            if (!isset(self::$_words_cache[$w->getName()]))
                self::$_words_cache[$w->getName()] = $w->getWord();
        }
    }

    /**
     * Add new work to database
     * @param array $data
     * @return bool
     */
    public static function addWord($data)
    {
        $languages = Languages::getPairs();
        if (!$languages) {
            return false;
        }

        if (!$data || !isset($data['word'])) {
            if (isset($data['name'])) {
                $data['word'] = [];

                foreach ($languages as $k => $v) {
                    $data['word'][$k] = '';
                }
            } else {
                return false;
            }
        }

        // No name - make it like text from current language
        if (!$data['name']) {
            $data['name'] = $data['word'][LNG];
        }

        // Check word for every language
        foreach ($languages as $k => $v) {
            $entity = PagesWordEntityRepository::findOneEntityByCriteria(['name' => $data['name'] . '_' . $k]);

            // Found and skip
            if ($entity) {
                continue;
            }

            // Create one word for that exact language
            $entity = new PagesWordEntity();
            $entity->setName($data['name'] . '_' . $k);
            $entity->setWord($data['word'][$k]);
            $entity->save();
        }

        return true;
    }

    /**
     * Get pages that are in main menu only
     * @param string $lng
     * @param string $menu_name
     * @return array
     */
    public static function getMainMenu($lng = LNG, $menu_name = '')
    {
        $languages = Languages::getPairs();

        $data = [];

        // Empty set for non-existing language
        if (!isset($languages[$lng])) {
            return $data;
        }

        // Try cache
        $cache_key = 'structure_main_menu' . $lng;
        if (Settings::isCacheEnabled()) {
            $data = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if ($data) {
                return $data;
            }
        }

        // Generate data set of pages
        if (!$data || !is_array($data)) {
            $entity = PageEntityRepository::findOneEntityByCriteria([
                'location' => $lng
            ]);

            if (!$entity) {
                return $data;
            }

            $data = self::getSubpages($entity->getId(), true, $menu_name);

            // Save in cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()->set($cache_key, $data);
            }
        }

        return $data;
    }

    /**
     * Get sub pages of exact page
     * @param int    $pid
     * @param bool   $only_in_menu
     * @param string $menu_name
     * @return array
     */
    public static function getSubpages($pid = 0, $only_in_menu = true, $menu_name = '')
    {
        $collection = new PageEntityRepository();

        // If need to group by parent
        if ($pid) {
            $collection->setWherePid($pid);
        }

        // Only active for front site
        if (MODE === 'site') {
            $collection->setWhereActive(1);
        }

        // Only enabled in menu
        if ($only_in_menu) {
            $collection->setWhereInMenu(1);
        }

        // Only for exact menu
        if ($menu_name) {
            $collection->setWhereMenuName($menu_name);
        }

        $collection->addOrderByField('order');

        // Common cache
        if (Settings::isCacheEnabled()) {
            $collection->enableUsingCache();
        }

        // Return as aggregated data array
        return $collection->getAsArrayOfObjectData(true);
    }

    /**
     * Used in admin panel only as a shorthand function when changing component data
     * @param int $id
     */
    public static function savePageComponentsToHistory($id)
    {
        $id = (int)$id;

        $version = new PageComponentHistoryRepository();
        $version->setWherePageId($id);
        $version->addSimpleSelectFieldsAsString('MAX(`version`) AS `version`');

        /** @var PageComponentHistory $version */
        $version = $version->getFirstObjectFromCollection();
        if ($version) {
            $version = $version->getVersion();
        } else {
            $version = 0;
        }

        $original_data_all = new PageComponentEntityRepository();
        $original_data_all->setWherePageId($id);

        // Save changes
        foreach ($original_data_all->getAsArrayOfObjects() as $original_data) {
            /** @var PageComponentEntity $original_data */
            $new_data = new PageComponentHistory();
            $new_data->loadDataFromArray([
                'page_id'   => $original_data->getPageId(),
                'component' => $original_data->getComponent(),
                'data'      => $original_data->getData(),
                'ts'        => NOW,
                'user_id'   => USER_ID,
                'version'   => $version + 1
            ]);
            $new_data->save();
        }
    }


    /**
     * Used in admin panel only to generate structure XML file
     */
    public static function generateStructureXml()
    {
        // TODO set run in in background

        set_time_limit(1800);

        $host = CFG_DOMAIN;
        $protocol = CFG_PROTOCOL . '://';

        // New way - no need to supply all entities to generate URLS to, but page crawler will find all links itself
        $pages = array_keys(PageCrawler::getSiteLinks($protocol . $host, $host, $protocol));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($pages as $v) {
            $depth = substr_count($v, '/');

            // Calculate priority for url
            $priority = 0.3;
            if ($depth < 7) {
                $priority += 0.2;
            }
            if ($depth < 5) {
                $priority += 0.2;
            }

            $last_modification_ts = NOW;
            $page = Structure::getPageDataByPath($v);
            if ($page && isset($page['last_modification_ts'])) {
                $last_modification_ts = $page['last_modification_ts'];
            }

            $xml .= '
<url>
    <loc>' . str_replace(['\'', '"'], ['&apos;', '&quot;'], htmlspecialchars($v)) . '</loc>
    <lastmod>' . date('Y-m-d', $last_modification_ts) . '</lastmod>
    <changefreq>weekly</changefreq>
    <priority>' . $priority . '</priority>
</url>';
        }

        $xml .= '
</urlset>';

        $xml = strtolower($xml);

        // XML file
        $path = DIR_BASE . 'sitemap.xml';

        // Write dta to file
        $fh = fopen($path, 'w');
        flock($fh, LOCK_EX);
        fwrite($fh, $xml);
        fclose($fh);

        // GZ compressed file
        $path = DIR_BASE . 'sitemap.gz';

        if (!file_exists($path)) {
            $fh = gzopen($path, 'w9');
            flock($fh, LOCK_EX);
            gzwrite($fh, file_get_contents($path));
            gzclose($fh);
        }

        FrontendLogger::getInstance()->log('Sitemap XML generated');
    }

    /**
     * Get page data based on url
     * @param string $url
     * @return array|null
     */
    public static function getPageDataByPath($url)
    {
        $page_id = self::getIdByPath($url);

        return $page_id ? self::getPageDataById($page_id) : NULL;
    }

    /**
     * @param int $page_id
     * @return array|null
     */
    public static function getPageDataById($page_id = PAGE_ID)
    {
        if (isset(self::$_page_data_cache[$page_id])) {
            return self::$_page_data_cache[$page_id];
        }

        $cache_key = 'page_data_' . $page_id;
        if (Settings::isCacheEnabled()) {
            self::$_page_data_cache[$page_id] = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
            if (self::$_page_data_cache[$page_id]) {
                return self::$_page_data_cache[$page_id];
            }
        }

        $page_object_collection = new PageEntity($page_id);

        self::$_page_data_cache[$page_id] = $page_object_collection->getAsArray();

        // Common cache
        if (Settings::isCacheEnabled()) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, self::$_page_data_cache[$page_id]);
        }

        return self::$_page_data_cache[$page_id];
    }

    /**
     * Shorthand function to clear all caches
     */
    public static function clearCache()
    {
        Cacher::getInstance()->clearAllCaches();
    }

    /**
     * Get disabled components
     * @param bool $id
     * @return array
     */
    public static function getDisabledComponents($id = false)
    {
        if (!$id) {
            $id = PAGE_ID;
        }

        // Try cache
        $cache_key = 'disabled_components_' . $id;
        $res = NULL; // Use null to prevent db queries
        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
        }

        // Get from db
        if ($res === NULL) {
            $disabled_components = new PageComponentsDisabledEntityRepository();
            $disabled_components->setWherePageId($id);

            $res = $disabled_components->getPairs('class');

            // Save in cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()
                    ->set($cache_key, $res);
            }
        }

        return $res;
    }

    /**
     * Refresh template list in admin panel
     */
    public static function refreshTemplatesInDb()
    {
        FileSystem::mkDir(DIR_FRONT_TEMPLATES);

        // Make list of existing files
        $template_files = [];
        foreach (scandir(DIR_FRONT_TEMPLATES) as $v) {
            if ($v[0] === '.') {
                continue; // Skip hidden files
            }

            if (is_dir(DIR_FRONT_TEMPLATES . $v)) {
                foreach (scandir(DIR_FRONT_TEMPLATES . $v) as $value) {
                    if ($value[0] === '.') {
                        continue; // Skip hidden files
                    }

                    if (is_file(DIR_FRONT_TEMPLATES . $v . '/' . $value)) {
                        $template_files[] = $v . '/' . $value;
                    }
                }
            }
        }

        $templates_in_db = new PageTemplateEntityRepository();
        $templates_in_db = $templates_in_db->getPairs('file');

        $unknown_templates = $templates_in_db;
        // Insert into DB new files
        foreach ($template_files AS $v) {
            if (!in_array($v, $templates_in_db)) {
                // Add file in DB
                $template = new PageTemplateEntity();
                $template->setFile($v);
                $template->save();
            }

            if (($key = array_search($v, $unknown_templates)) !== false) {
                unset($unknown_templates[$key]);
            }
        }

        foreach ($unknown_templates as $v) {
            $template = new PageTemplateEntityRepository();
            $template->setWhereFile($v);
            $template->deleteObjectCollection();
        }
    }

    /**
     * Return page data based on page label
     * @param string $label
     * @param string $lng
     * @return array|null
     */
    public static function getPageDataByLabel($label, $lng = LNG)
    {
        $page_id = self::getIdByLabel($label, $lng);

        return $page_id ? self::getPageDataById($page_id) : NULL;
    }

    /**
     * Used in admin panel in a few places
     * @param int $pid
     * @return array
     */
    public static function getPagesAsTreeForSelects($pid = 0)
    {
        return TableTree::getInstance('cms_pages')
            ->setTitleColumn('location')
            ->setOrderColumn('order')
            ->getAsArray($pid);
    }

    /**
     * @param int $id
     * @return PageEntity
     */
    public static function getParentPage($id = PAGE_ID)
    {
        $page = new PageEntity($id);

        return new PageEntity($page->getPid());
    }

    /**
     * @param int $page_id
     *
     * @return array
     */
    public static function getCachedComponents($page_id = 0)
    {
        if (!$page_id) {
            $page_id = PAGE_ID;
        }

        // Try cache
        $cache_key = 'cached_components_' . $page_id;
        $res = NULL; // Use null to prevent db queries
        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
        }

        // Get from db
        if ($res === NULL) {
            $cached_components = new PageComponentsCachedEntityRepository();
            $cached_components->setWherePageId($page_id);

            $res = $cached_components->getPairs('class');

            // Save in cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()
                    ->set($cache_key, $res);
            }
        }

        return $res;
    }
}