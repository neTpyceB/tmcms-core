<?php
use TMCms\Templates\Plugin;

class emailPlugin extends Plugin
{
    public static function getComponents() {
        return array(
            'email' => array(
                'type' => 'textarea',
                'edit' => 'wysiwyg'
            )
        );
    }

    public function render()
    {
        ?>
        <input type=email value="<?=$this->getValue('email')?>">
        <?php
    }
}