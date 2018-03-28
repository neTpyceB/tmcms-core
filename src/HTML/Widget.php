<?php

namespace TMCms\HTML;

defined('INC') or exit;

/**
 * Class Widget
 */
abstract class Widget
{
    /**
     * @var Element
     */
    protected $owner;
    protected $popup_url;

    public $wysiwyg_options = [];

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setModalPopupAjaxUrl($url) {
        $this->popup_url = $url;

        return $this;
    }

    /**
     * @param Element $owner
     * @return $this
     */
    public function setOwner(Element $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function __toString();
}
