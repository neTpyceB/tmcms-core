<?php

namespace TMCms\HTML\Cms;

use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

/**
 * Class CmsTabs
 */
class CmsTabs
{
    use singletonInstanceTrait;

    private $tabs = [];
    private $have_selected_tab = false;
    private $caption_title;

    public function addTab($title = '', $content = '', $active = false)
    {
        if (!$title) {
            $title = count($this->tabs) + 1;
        }

        if ($active) {
            $this->have_selected_tab = true;
        }

        $this->tabs[] = [
            'title' => $title,
            'content' => $content,
            'active' => $active
        ];

        return $this;
    }

    public function getTabs() {
        return $this->tabs;
    }

    public function setCaptionTitle($title) {
        return $this->caption_title = $title;
    }

    /**
     * @return string
     */
    public function  __toString()
    {
		if (!$this->tabs) {
			return 'No data';
		}

        // Must have any selected
        if (!$this->have_selected_tab) {
            $this->tabs[0]['active'] = true;
        }

        $titles = $contents = [];
        foreach ($this->getTabs() as $k => $tab) {
            $titles[] = '<li'. ($tab['active'] ? ' class="active"' : '') .'><a href="#'. $tab['title'] .'" data-toggle="tab">'. Converter::symb2Ttl($tab['title']) .'</a></li>';
            $contents[] = '<div class="tab-pane fade'. ($tab['active'] ? ' active' : '') .' in" id="'. $tab['title'] .'">' . $tab['content'] . '</div>';
        }
        if ($this->caption_title) {
            $titles[] = '<li style="padding-top: 13px; padding-left: 13px; font-weight: 600;" class="text-muted">Translations</li>';
        }

        ob_start();
        ?>
        <div class="tabbable tabbable-custom boxless tabbable-reversed">
            <ul class="nav nav-tabs">
                <?= implode('', $titles) ?>
            </ul>
            <div class="tab-content">
                <?= implode('', $contents) ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}