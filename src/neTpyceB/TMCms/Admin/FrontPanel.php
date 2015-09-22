<?php

namespace neTpyceB\TMCms\Admin;

defined('INC') or exit;

/**
 * Class FrontPanel
 * @package neTpyceB\TMCms\Admin
 */
class FrontPanel
{
    /**
     * @return string
     */
    public static function getView()
    {
        ob_start();
        ?>
        <script>
            $.ajax({
                url: '/-/ajax/admin_front_panel/',
                success: function (data) {
                    $('body').append(data);
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
}