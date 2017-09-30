<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;

defined('INC') or exit;

/**
 * Use it to create custom button with custom javascript
 *
 * Class Custom
 */
class Custom extends Widget
{
    private $onclick = '';

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = NULL)
    {
        parent::__construct($owner);
    }

    /**
     * @param Element $owner
     *
     * @return Custom
     */
    public static function getInstance(Element $owner = NULL)
    {
        return new self($owner);
    }

    /**
     * @param string $js
     *
     * @return $this
     */
    public function setOnclick(string $js)
    {
        $this->onclick = $js;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnclick(): string
    {
       return $this->onclick;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        ?>
        <input
            data-popup-url="<?= $this->popup_url ?>&nomenu&selector=<?= $this->owner->getId() ?>&cache=<?= NOW ?>"
            data-popup-result-destination="<?= $this->owner->getId() ?>"
            type="button"
            value="Select"
            onclick="<?= $this->onclick ?>"
            class="btn btn-info">
        <?php

        return ob_get_clean();
    }
}