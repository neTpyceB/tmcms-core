<?php

namespace neTpyceB\TMCms\Templates;

defined('INC') or exit;

/**
 * Class PageBody
 * @package neTpyceB\TMCms\Templates
 */
class PageBody
{
    /**
     * @var string
     */
    private $content = '';

    /**
     * @param string $content
     */
    public function __construct($content = '')
    {
        return $this->setContent($content);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
}