<?php
use TMCms\Templates\Plugin;

class imagePlugin extends Plugin
{
    public static function getComponents() {
        return array(
            'image' => array(
                'type' => 'text',
                'edit' => 'files',
                'path' => DIR_PUBLIC_URL
            ),
            'alt_text'
        );
    }

    public function render()
    {
        ?>
        <img src="<?=$this->getValue('image')?>" alt="<?=$this->alt_text?>">
        <?php
    }
}