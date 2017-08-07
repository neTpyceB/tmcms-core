<?php
declare(strict_types=1);

namespace TMCms\Network;

defined('INC') or exit;

use TMCms\Traits\singletonInstanceTrait;

class PageCrawler
{
    use singletonInstanceTrait;

    const RETURN_HEADERS = 'headers';
    const RETURN_CONTENT = 'content';
    const RETURN_STORE = 'store';
    protected static $maximum_link_count = 0; // 0 is no limit

    /**
     * @param string $page
     * @param string $host
     * @param string $scheme
     * @param array  $do_not_follow_links
     * @param array  $extensions_to_parse
     * @param array  $already_parsed_urls
     *
     * @return array
     */
    public static function getSiteLinks(string $page, string $host, string $scheme, array $do_not_follow_links = [], array $extensions_to_parse = ['php', 'html'], array $already_parsed_urls = []): array
    {
        // Already checked - skip
        if (isset($already_parsed_urls[$page]) && $already_parsed_urls[$page] === true) {
            return $already_parsed_urls;
        }

        // If no content - skip
        $content = @file_get_contents($page);

        if (!$content) {
            unset($already_parsed_urls[$page]);

            return $already_parsed_urls;
        }

        // Set link as checked
        $already_parsed_urls[$page] = true;

        // Check if we do not need to go to the link, in meta
        if (preg_match('/<[Mm][Ee][Tt][Aa].*[Nn][Aa][Mm][Ee]=.?("|\'|).*[Rr][Oo][Bb][Oo][Tt][Ss].*?("|\'|).*?[Cc][Oo][Nn][Tt][Ee][Nn][Tt]=.*?("|\'|).*([Nn][Oo][Ff][Oo][Ll]{...}[Oo][Ww]|[Nn][Oo][Ii][Nn][Dd][Ee][Xx]|[Nn][Oo][Nn][Ee]).*?("|\'|).*>/', $content)) {
            $content = '';
        }

        // Get all links from page
        preg_match_all("/<[Aa][\s]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\s]*([^ \"'>\s#]+)[^>]*>/", $content, $tmp);

        // Add add only links without "nofollow"
        $links = [];
        foreach ($tmp[0] as $k => $v) {
            if (!preg_match('/<.*[Rr][Ee][Ll]=.?("|\'|).*[Nn][Oo][Ff][Oo][Ll]{...}[Oo][Ww].*?("|\'|)/', $v)) {
                $links[$k] = $tmp[1][$k];
            }
        }

        // Here we count links in every iteration, do not move it to external variable
        /** @noinspection CallableInLoopTerminationConditionInspection */
        for ($i = 0; $i < count($links); $i++) {
            // Stop function if too many links
            if (self::$maximum_link_count && count($already_parsed_urls) > self::$maximum_link_count) {
                return $already_parsed_urls;
            }

            // Can set external domain, so we check and set as main
            if (false === strpos($links[$i], $scheme . $host)) {
                $links[$i] = $scheme . $host . $links[$i];
            }

            // Remove js anchors
            $links[$i] = preg_replace('/#.*/X', '', $links[$i]);

            // Get info about link
            $url_info = parse_url($links[$i]);
            if (!isset($url_info['path'])) {
                $url_info['path'] = '';
            }

            // TODO check it for errors
            // Bad link, maybe mail or something similar
            if ((isset($url_info['host']) && $url_info['host'] !== $host)
                || $url_info['path'] === '/'
                || isset($already_parsed_urls[$links[$i]])
                || false !== strpos($links[$i], '@')
                || false !== strpos($links[$i], 'mailto:'
                    || false !== strpos($links[$i], 'tel:'
                    ))
            ) {
                continue;
            }

            // Link is in skip list?
            $do_not_follow_this_link = false;
            if ($do_not_follow_links) {
                foreach ($do_not_follow_links as $one_link) {
                    if (false !== strpos($links[$i], $one_link)) {
                        $do_not_follow_this_link = true;
                        break;
                    }
                }
            }
            // Skip link if should not follow it
            if ($do_not_follow_this_link) {
                continue;
            }

            // Check link extension
            $array_of_path_data = explode('.', $url_info['path']);
            $extension = end($array_of_path_data);
            $no_extension = false;

            if ($extension !== '' && false !== strpos($url_info['path'], '.') && 0 !== count($extensions_to_parse)) {
                $no_extension = true;
                foreach ($extensions_to_parse as $skip_extension) {
                    if ($extension === $skip_extension) {
                        $no_extension = false;
                        continue;
                    }
                }
            }

            // Page have no extension
            if ($no_extension) {
                continue;
            }

            // Finally add link to the list to check in future iterations
            $already_parsed_urls[$links[$i]] = false;

            // And go check links further and deeper
            $already_parsed_urls = self::getSiteLinks($links[$i], $host, $scheme, $do_not_follow_links, $extensions_to_parse, $already_parsed_urls);
        }

        return $already_parsed_urls;
    }
}