<?php

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;

defined('INC') or exit;

/**
 * Class GoogleMap
 */
class GoogleMap extends Widget {

    public $options = [];

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = null) {
        parent::__construct($owner);
    }

    public static function getInstance(Element $owner = null) {
        return new self($owner);
    }

    public function __toString() {
        ob_start();
        $options = !empty($this->options) ? ('&'.http_build_query($this->options)) : '';
        ?><input
        data-popup-url="?p=components&do=google_map&selector=<?= $this->owner->getId() ?>&nomenu&cache=<?= NOW ?><?= $options ?>"
        type="button" value="Google Map" data-popup-result-destination="<?= $this->owner->getId() ?>"
        class="btn btn-info"><?php
        return ob_get_clean();
    }
}