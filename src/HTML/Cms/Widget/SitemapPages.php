<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;

defined('INC') or exit;

/**
 * Class SitemapPages
 */
class SitemapPages extends Widget {

    public $multiple_rows = false;
    public $options = [];
    private $_lng = LNG;

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = null) {
        parent::__construct($owner);
    }
    /**
     * @param Element $owner
     *
     * @return $this
     */
    public static function getInstance(Element $owner = null) {
        return new self($owner);
    }

    /**
     * @param string $lng
     *
     * @return $this
     */
    public function setLanguage(string $lng)
    {
        $this->_lng = $lng;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        ob_start();

        ?>
        <input
            data-popup-url="?p=components&do=pages&nomenu&lng=<?= $this->_lng ?>&cache=<?= NOW ?><?= empty($this->options) ? '' : '&' . http_build_query($this->options) ?>"
            type="button"
            value="Sitemap"
            data-popup-result-destination="<?= $this->owner->getId() ?>"
            class="btn btn-info"
        >
        <?php

        return ob_get_clean();

    }
}