<?php

namespace TMCms\Strings;

use XMLWriter;

defined('INC') or exit;

/**
 * Class SitemapXMLGenerator
 */
class SitemapXMLGenerator
{
    private $generation_total_time = 0;
    private $generation_start_time = 0;

    private $path = DIR_BASE . 'sitemap/';

    /**
     *
     * @var XMLWriter
     */
    private $writer;
    private $domain;
    private $filename = 'sitemap';
    private $current_item = 0;
    private $current_sitemap = 0;

    const EXT = '.xml';
    const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const DEFAULT_PRIORITY = 0.5;
    const ITEM_PER_SITEMAP = 50000;
    const FILE_SEPARATOR = '-';
    const INDEX_SUFFIX = 'index';

    public function __construct()
    {
        // Can be executed long enough
        set_time_limit(1800);
        $this->generation_start_time = microtime(true);

        $this->setDomain(CFG_DOMAIN);

        // Add main page
        $this->addItem('/', '1.0', 'weekly', 'Today');
    }

    public function generate() {
        // Set usual urls
        foreach (array_keys(self::getSiteLinks(CFG_PROTOCOL . $this->getDomain(), $this->getDomain(), CFG_PROTOCOL, ['/404/'], ['php', 'html'], [])) as $k => $url) {
            if (!$k++) {
                continue; // Skip main page
            }

            // Add page link
            $url = str_replace(CFG_PROTOCOL . $this->getDomain(), '', $url);
            $this->addItem($url, '0.7', 'weekly', 'Today');
        }

        $this->createSitemapIndex(CFG_PROTOCOL . $this->getDomain() . '/sitemap/', 'Today');

        $this->generation_total_time = round(microtime(true) - $this->generation_start_time, 5);
    }

    /**
     * Sets root path of the website, starting with http:// or https://
     *
     * @param string $domain
     * @return $this
     */
    private function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Returns root path of the website
     *
     * @return string
     */
    private function getDomain()
    {
        return $this->domain;
    }

    /**
     * Returns XMLWriter object instance
     *
     * @return XMLWriter
     */
    private function getWriter()
    {
        return $this->writer;
    }

    /**
     * Assigns XMLWriter object instance
     *
     * @param XMLWriter $writer
     */
    private function setWriter(XMLWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Returns path of sitemaps
     *
     * @return string
     */
    private function getPath()
    {
        return $this->path;
    }

    /**
     * Sets paths of sitemaps
     *
     * @param string $path
     * @return $this
     */
    private function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns filename of sitemap file
     *
     * @return string
     */
    private function getFilename()
    {
        return $this->filename;
    }

    /**
     * Sets filename of sitemap file
     *
     * @param string $filename
     * @return $this
     */
    private function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Returns current item count
     *
     * @return int
     */
    private function getCurrentItem()
    {
        return $this->current_item;
    }

    /**
     * Increases item counter
     *
     */
    private function incCurrentItem()
    {
        $this->current_item = $this->current_item + 1;
    }

    /**
     * Returns current sitemap file count
     *
     * @return int
     */
    private function getCurrentSitemap()
    {
        return $this->current_sitemap;
    }

    /**
     * Increases sitemap file count
     *
     */
    private function incCurrentSitemap()
    {
        $this->current_sitemap = $this->current_sitemap + 1;
    }

    /**
     * Prepares sitemap XML document
     *
     */
    private function startSitemap()
    {
        $this->setWriter(new XMLWriter());
        if ($this->getCurrentSitemap()) {
            $this->getWriter()->openURI($this->getPath() . $this->getFilename() . self::FILE_SEPARATOR . $this->getCurrentSitemap() . self::EXT);
        } else {
            $this->getWriter()->openURI($this->getPath() . $this->getFilename() . self::EXT);
        }

        $this->getWriter()->startDocument('1.0', 'UTF-8');
        $this->getWriter()->setIndent(true);
        $this->getWriter()->startElement('urlset');
        $this->getWriter()->writeAttribute('xmlns', self::SCHEMA);
    }

    /**
     * Adds an item to sitemap
     *
     * @param string $loc URL of the page. This value must be less than 2,048 characters.
     * @param float $priority The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
     * @param string $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
     * @param string|int $lastmod The date of last modification of url. Unix timestamp or any English textual datetime description.
     * @return $this
     */
    private function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL)
    {
        if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
            if ($this->getWriter() instanceof XMLWriter) {
                $this->endSitemap();
            }

            $this->startSitemap();
            $this->incCurrentSitemap();
        }

        $this->incCurrentItem();
        $this->getWriter()->startElement('url');
        $this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
        $this->getWriter()->writeElement('priority', $priority);

        if ($changefreq) {
            $this->getWriter()->writeElement('changefreq', $changefreq);
        }

        if ($lastmod) {
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
        }

        $this->getWriter()->endElement();

        return $this;
    }

    /**
     * Prepares given date for sitemap
     *
     * @param string $date Unix timestamp or any English textual datetime description
     * @return string Year-Month-Day formatted date.
     */
    private function getLastModifiedDate($date)
    {
        if (ctype_digit($date)) {
            return date('Y-m-d', $date);
        } else {
            $date = strtotime($date);
            return date('Y-m-d', $date);
        }
    }

    /**
     * Finalizes tags of sitemap XML document.
     *
     */
    private function endSitemap()
    {
        if (!$this->getWriter()) {
            $this->startSitemap();
        }

        $this->getWriter()->endElement();
        $this->getWriter()->endDocument();
    }

    /**
     * Writes Google sitemap index for generated sitemap files
     *
     * @param string $loc Accessible URL path of sitemaps
     * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
     */
    private function createSitemapIndex($loc, $lastmod = 'Today')
    {
        $this->endSitemap();
        $indexwriter = new XMLWriter();
        $indexwriter->openURI($this->getPath() . $this->getFilename() . self::FILE_SEPARATOR . self::INDEX_SUFFIX . self::EXT);
        $indexwriter->startDocument('1.0', 'UTF-8');
        $indexwriter->setIndent(true);
        $indexwriter->startElement('sitemapindex');
        $indexwriter->writeAttribute('xmlns', self::SCHEMA);

        for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
            $indexwriter->startElement('sitemap');
            $indexwriter->writeElement('loc', $loc . $this->getFilename() . ($index ? self::FILE_SEPARATOR . $index : '') . self::EXT);
            $indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
            $indexwriter->endElement();
        }

        $indexwriter->endElement();
        $indexwriter->endDocument();
    }

    private static function getSiteLinks($page, $host, $scheme, $no_follow, $extensions, $urls)
    {
        // Already checked - skip
        if (isset($urls[$page]) && $urls[$page] == 1) {
            return false;
        }

        // If no content - skip
        $content = file_get_contents($page);
        if (!$content) {
            unset($urls[$page]);
            return false;
        }

        // Set link as checked
        $urls[$page] = 1;

        // Check if we do not need to go to the link
        if (preg_match('/<[Mm][Ee][Tt][Aa].*[Nn][Aa][Mm][Ee]=.?("|\'|).*[Rr][Oo][Bb][Oo][Tt][Ss].*?("|\'|).*?[Cc][Oo][Nn][Tt][Ee][Nn][Tt]=.*?("|\'|).*([Nn][Oo][Ff][Oo][Ll][Ll][Oo][Ww]|[Nn][Oo][Ii][Nn][Dd][Ee][Xx]|[Nn][Oo][Nn][Ee]).*?("|\'|).*>/', $content)) {
            $content = NULL;
        }

        // Get all links from page
        preg_match_all("/<[Aa][\s]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\s]*([^ \"'>\s#]+)[^>]*>/", $content, $tmp);
        $content = NULL;

        // Add all links with "follow"
        $links = [];
        foreach ($tmp[0] as $k => $v) {
            if (!preg_match('/<.*[Rr][Ee][Ll]=.?("|\'|).*[Nn][Oo][Ff][Oo][Ll][Ll][Oo][Ww].*?("|\'|).*/', $v)) {
                $links[$k] = $tmp[1][$k];
            }
        }
        unset($tmp);

        for ($i = 0; $i < count($links); $i++) {
            // Stop function if too many links
            if (count($urls) > 10) {
                return $urls;
            }

            // Can set external domain, so we check and set nicgade as main
            if (!strstr($links[$i], $scheme . $host)) {
                $links[$i] = $scheme . $host . $links[$i];
            }

            // Remove js anchors
            $links[$i] = preg_replace("/#.*/X", "", $links[$i]);
            //Узнаём информацию о ссылке
            $url_info = @parse_url($links[$i]);
            if (!isset($url_info['path'])) {
                $url_info['path'] = NULL;
            }

            // Bad link, maybe mail or smth
            if ((isset($url_info['host']) AND $url_info['host'] != $host) OR $url_info['path'] == '/' OR isset($urls[$links[$i]]) OR strstr($links[$i], '@')) {
                continue;
            }

            // Link is in skip list?
            $do_not_follow_this_link = 0;
            if ($no_follow != NULL) {
                foreach ($no_follow as $of) {
                    if (strstr($links[$i], $of)) {
                        $do_not_follow_this_link = 1;
                        break;
                    }
                }
            }
            if ($do_not_follow_this_link == 1) {
                continue;
            }

            // Check link extension
            $arr = explode('.', $url_info['path']);
            $ext = end($arr);
            $no_extension = 0;
            if ($ext != '' AND strstr($url_info['path'], '.') AND count($extensions) != 0) {
                $no_extension = 1;
                foreach ($extensions as $of) {
                    if ($ext == $of) {
                        $no_extension = 0;
                        continue;
                    }
                }
            }
            if ($no_extension == 1) {
                continue;
            }

            // Finally set link to the list
            $urls[$links[$i]] = 0;

            // And go  check links further
            $urls = self::getSiteLinks($links[$i], $host, $scheme, $no_follow, $extensions, $urls);
        }
        return $urls;
    }
}