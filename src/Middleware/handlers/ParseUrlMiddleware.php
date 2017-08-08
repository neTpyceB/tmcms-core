<?php
declare(strict_types=1);

use TMCms\Network\Domains;
use TMCms\Network\SearchEngines;
use TMCms\Routing\Interfaces\IMiddleware;

class ParseUrlMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        /* Parse URL */
        $parse_url = SELF;
        if ($parse_url === '/index.php') {
            $parse_url = $_SERVER['REQUEST_URI'];
        }

        $path = [];
        if ((!$url = parse_url($parse_url)) || !isset($url['path'])) {
            throw new RuntimeException('Wrong URL and can not be parsed');
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

        define('PATH_PART_COUNT', count($path));
        define('PATH_ROUTER', $path);
        define('PATH_ORIGINAL', ($path ? '/' . implode('/', $path) : '') . '/');
        define('PATH', '/' . implode('/', $path) . ($path ? '/' : ''));

        // In case user came from search engine
        define('REF_DOMAIN', REF ? Domains::getDomainName(REF) : '');
        define('REF_SEARCH_ENGINE_KEYWORD', REF ? (REF_DOMAIN === CFG_DOMAIN ? '' : SearchEngines::getSearchWord(REF)) : '');
    }
}