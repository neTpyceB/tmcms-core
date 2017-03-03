<?php

namespace TMCms\Routing;

use TMCms\Admin\Entity\LanguageEntity;
use TMCms\Admin\Entity\LanguageEntityRepository;
use TMCms\Admin\Structure\Entity\PageEntityRepository;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\Orm\Entity;

defined('INC') || exit;

/**
 * Class Languages
 */
class Languages
{
    /**
     * @var array
     */
    private static $language_pairs; // Do not init as array

    /**
     * Check if language exists
     * @param string $short
     * @return bool
     */
    public static function exists($short)
    {
        // Try cache
        $cache_key = 'language_exists_' . $short;
        $res = NULL;
        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()
                ->getDefaultCacher()
                ->get($cache_key)
            ;
        }

        // Get from db
        if ($res === NULL) {
            $languages = new LanguageEntityRepository();
            $languages->setWhereShort($short);
            $res = (bool)$languages->getFirstObjectFromCollection();

            // Save in cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()
                    ->getDefaultCacher()
                    ->set($cache_key, $res)
                ;
            }
        }
        return $res;
    }

    /**
     * @return array
     */
    public static function getPairs()
    {
        if (isset(self::$language_pairs)) {
            return self::$language_pairs;
        }

        // For frontend
        if (MODE === 'site') {
            $pairs = array();
            if (Settings::isCacheEnabled()) {
                $pairs = Cacher::getInstance()
                    ->getDefaultCacher()
                    ->get('structure_language_pairs')
                ;
            }
            if (!$pairs) {
                // Get languages which have active main page for them
                $languages = new LanguageEntityRepository();

                $pages = new PageEntityRepository();
                $pages->setWherePid(0);
                $pages->setWhereInMenu(1);
                $pages->setWhereActive(1);
                $pages->addOrderByField('order');

                $languages->mergeWithCollection($pages, 'short', 'location');

                if (Settings::isCacheEnabled()) {
                    $languages->enableUsingCache();
                }

                self::$language_pairs = $languages->getPairs('full', 'short');

                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()
                        ->getDefaultCacher()
                        ->set('structure_language_pairs', self::$language_pairs)
                    ;
                }
            } else {
                self::$language_pairs = $pairs;
            }

            return self::$language_pairs;
        }

        // For backend
        $languages = new LanguageEntityRepository();
        $languages->addOrderByField('short');

        return self::$language_pairs = $languages->getPairs('full', 'short');
    }

    /**
     * Gets url of same page for selected language
     * @param string $lng language to get page link to
     * @param null|Entity $object to postfix path
     * @return bool|string path to page in selected language
     */
    public static function getUrl($lng, $object = NULL)
    {
        if (!self::exists($lng)) {
            return false;
        }

        // Default path
        $res = '/'. $lng .'/';

        $router = Router::getInstance();
        $current_page = $router->getPageData();
        $path = $router->getPath();

        // Try to find by same label
        $page_url_by_label = Structure::getPathByLabel($current_page['string_label'], $lng, false);
        if ($page_url_by_label) {
            $res = $page_url_by_label;
        } else {
            // No label page, compose path by location
            $p = [];
            foreach ($path as $v) {
                if (isset($v['location'])) {
                    $p[] = $v['location'];
                }
            }
            $p[0] = $lng;
            $p = '/' . implode('/', $p) . '/';

            // If have page in Structure
            if (Structure::getIdByPath($p)) {
                $res = $p;
            }
        }

        $skip_get_path = false;
        if ($object) {
            $object_slug = $object->getSlugUrl($lng);
            if ($object_slug) {
                $res .= $object_slug . '/';
            }
            $skip_get_path = true;
        }

        // $_GET in path
        $so = count($_GET);
        $path_gets = [];
        $param_get = $_GET;
        $skip = false;

        for ($i = 0; $i < $so; $i++) {
            // Skip next if non-next
            if ($skip) {
                continue;
            }

            if (isset($_GET[$i])) {
                if(!$skip_get_path)
                    $path_gets[] = $_GET[$i];
                unset($param_get[$i]);
            } else {
                $skip = true;
            }
        }

        // If have transparent get
        if ($path_gets) {
            $res .= implode('/', $path_gets) . '/';
        }

        // If have usual get params
        if ($param_get) {
            $res .= '?' . http_build_query($param_get);
        }

        return $res;
    }

    /**
     * @param string $short
     * @return int
     */
    public static function getIdByShort($short)
    {
        $language_collection = new LanguageEntityRepository();
        $language_collection->setWhereShort($short);
        $language = $language_collection->getFirstObjectFromCollection();

        return $language ? $language->getId() : NULL;
    }

    /**
     * @param int $page_id
     * @return string
     */
    public static function getIdByPageId($page_id)
    {
        $data['pid'] = (int)$page_id;

        do {
            // Select top page to get most parent
            $page = PageEntityRepository::findOneEntityById($data['pid']);

            if ($page) {
                $data = $page->getAsArray();
            } else {
                $data['pid'] = 0; // Stop
            }
        } while ($data['pid']);

        return self::getIdByShort($data['location']);
    }

    /**
     * @param string $short
     * @return string
     */
    public static function getFullByShort($short)
    {
        $data = [];

        // Get from cache
        $cached_q = NULL;
        $cache_key = __METHOD__ . $short;

        if (Settings::isCacheEnabled()) {
            $cached_q = Cacher::getInstance()
                ->getDefaultCacher()
                ->get($cache_key)
            ;
            if ($cached_q !== NULL) {
                $data = $cached_q;
            }
        }

        if (!$data) {
            $language_collection = new LanguageEntityRepository();
            $language_collection->setWhereShort($short);
            /** @var LanguageEntity $language */
            $language = $language_collection->getFirstObjectFromCollection();
            $data = $language->getFull();

            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()
                    ->getDefaultCacher()
                    ->set($cache_key, $data)
                ;
            }
        }
        return $data;
    }

    /**
     * @param $short
     * @return array|NULL
     */
    public static function getLanguageDataByShort($short)
    {
        $language_collection = new LanguageEntityRepository();
        $language_collection->setWhereShort($short);

        if (Settings::isCacheEnabled()) {
            $language_collection->enableUsingCache();
        }

        $language = $language_collection->getFirstObjectFromCollection();

        return $language ? $language->getAsArray() : NULL;
    }

    public static function getTotalCountOfLanguage()
    {
        return count(self::getPairs());
    }
}