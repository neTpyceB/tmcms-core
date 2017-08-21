<?php

namespace TMCms\HTML;

/**
 * Class BreadCrumbsLink
 */
class BreadCrumbItem
{
    private $name, $href, $target_blank;

    /**
     * @param string $name
     * @param string $href
     * @param string $target
     */
    public function  __construct($name, $href, $target)
    {
        $this->name = $name && is_string($name) ? $name : (is_array($name) && isset($name[LNG]) ? $name[LNG] : '');
        $this->href = $href;
        $this->target_blank = $target;
    }

    /**
     * @return array
     */
    public function get()
    {
        return [
            'name' => $this->name,
            'href' => $this->href,
            'target' => $this->target_blank
        ];
    }

    /**
     * @return string
     */
    public function  __toString()
    {
        if ($this->href) {
            return '<a href="' . $this->href . '" ' . ($this->target_blank ? ' target="_blank"' : '') . '>' . $this->name . '</a>';
        }

        return (string)$this->name;
    }
}