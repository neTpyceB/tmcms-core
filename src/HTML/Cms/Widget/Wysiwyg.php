<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;
use TMCms\Templates\PageHead;

\defined('INC') or exit;

/**
 * Class Wysiwyg
 * @package TMCms\HTML\Cms\Widget
 */
class Wysiwyg extends Widget
{
    const FIELD_EDITOR_NAME = 'wysiwyg';

    public $wysiwyg_options = '';

    /**
     *
     * @param Element $owner
     * @return $this
     */
    public static function getInstance(Element $owner = null): self
    {
        return new self($owner);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        PageHead::getInstance()->addJsUrl('/vendor/devp-eu/tmcms-core/src/assets/tinymce/tinymce.js');

        ob_start();

        ?>
        <span class="input-group-btn">
            <button
                    class="btn btn-success"
                    type="button"
                    data-popup-url="?p=components&do=wysiwyg&selector=<?= $this->owner->getId() ?>&options=<?= $this->wysiwyg_options ?>&nomenu&cache=<?= NOW ?>"
                    data-popup-result-destination="#<?= $this->owner->getId() ?>"
                ><i class="fa fa-arrow-left fa-fw"></i>
                <?= __('Editor') ?>
            </button>
        </span>
        <?php

        return ob_get_clean();
    }
}
