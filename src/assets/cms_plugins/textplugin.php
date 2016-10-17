<?php
use TMCms\Templates\Plugin;

class textPlugin extends Plugin
{
    public static function getComponents() {
        return array(
            'title',
            'text' => array(
                'type' => 'textarea',
                'edit' => 'wysiwyg'
            )
        );
    }

    public function render()
    {
        ?>
        Title is <?=$this->title?> and textarea is <textarea><?=$this->getValue('text')?></textarea>
        <?php
    }
}