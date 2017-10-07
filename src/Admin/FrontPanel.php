<?php
declare(strict_types=1);

namespace TMCms\Admin;

use function defined;

defined('INC') or exit;

/**
 * Class FrontPanel
 * @package TMCms\Admin
 */
class FrontPanel
{
    /**
     * Add ajax call to load front panel after page is shown
     *
     * @return string
     */
    public static function getView()
    {
        ob_start();

        ?>
        <script>
            $.ajax({
                url: '/-/<?= CFG_API_ROUTE ?>/admin_front_panel/',
                data: {
                    page_id: '<?= defined('PAGE_ID') ? PAGE_ID : '0' ?>'
                },
                success: function (data) {
                    $('body').append(data);
                }
            });
        </script>
        <?php

        return ob_get_clean();
    }
}