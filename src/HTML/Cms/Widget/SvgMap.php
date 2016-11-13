<?php

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;

defined('INC') or exit;

/**
 * Class SvgMap
 */
class SvgMap extends Widget {
    private $svg_image_path = '';

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
        ?><input
            type="button"
            class="btn btn-info"
            value="<?= __('SVG Map') ?>"
            data-popup-url="?p=components&do=svg_map&nomenu&svg_image_path=<?= $this->svg_image_path ?>&cache=<?= NOW ?>"
            data-popup-width="700"
            data-popup-height="720"
            data-popup-result-destination="#<?= $this->owner->id() ?>"><?php
        return ob_get_clean();
    }

    public function setSvgImagePath($path)
    {
        $this->svg_image_path = $path;

        return $this;
    }
}