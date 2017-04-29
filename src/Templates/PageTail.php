<?php

namespace TMCms\Templates;

use TMCms\Files\Finder;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class PageTail
 * Generates content like scripts just before closing </body> tag
 */
class PageTail
{
    use singletonOnlyInstanceTrait;

    private
        $css_urls = [],
        $js_sequence = 0,
        $js_urls = [],
        $deferred_scripts = [],
        $js = [],
        $custom_strings = [];

    /**
     * @param string $url
     * @param string $media
     *
     * @return  $this
     */
    public function addCssUrl($url, $media = 'all')
    {
        $this->css_urls[$url] = $media;

        return $this;
    }

    /**
     * @param string $url
     * @param bool   $defer
     *
     * @return $this
     */
    public function addJsUrl($url, $defer = false)
    {
        if (!in_array($url, $this->js_urls)) {
            $this->js_urls[++$this->js_sequence] = $url;
            if ($defer) {
                $this->deferred_scripts[$this->js_sequence] = $this->js_sequence;
            }
        }

        return $this;
    }

    public function deleteJsUrl($url)
    {
        if (in_array($url, $this->js_urls)) {
            if (($key = array_search($url, $this->js_urls)) !== false) {
                unset($this->js_urls[$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $js
     *
     * @return  $this
     */
    public function addJs($js)
    {
        $this->js[++$this->js_sequence] = $js;

        return $this;
    }

    /**
     * Add custom string (element) into <head>
     *
     * @param $string
     *
     * @return $this
     */
    public function addCustomString($string)
    {
        $this->custom_strings[] = $string;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();
        // CSS files
        foreach ($this->css_urls as $k => $v): $k = Finder::getInstance()->searchForRealPath($k); ?>
            <link rel="stylesheet" type="text/css" href="<?= $k ?>" media="<?= $v ?>">
        <?php endforeach;

        // JS files and scripts
        for ($i = 1; $i <= $this->js_sequence; $i++) :
            if (isset($this->js_urls[$i])): $this->js_urls[$i] = Finder::getInstance()->searchForRealPath($this->js_urls[$i]); ?>
                <script src="<?= $this->js_urls[$i] ?>"<?= isset($this->deferred_scripts[$i]) ? ' defer' : '' ?>></script>
            <?php elseif (isset($this->js[$i])): ?>
                <script><?= $this->js[$i] ?></script>
            <?php endif;
        endfor;
        // Any custom strings
        foreach ($this->custom_strings as $v) echo $v;

        return ob_get_clean();
    }
}