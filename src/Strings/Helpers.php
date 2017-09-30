<?php
declare(strict_types=1);

namespace TMCms\Strings;

defined('INC') or exit;

/**
 * Use this class as example reference to create pagination
 *
 * Class Helpers
 * @package TMCms\Strings
 */
class Helpers
{
    public static function pager(int $page, int $per_page, int $total, array $filters = [], bool $found = false)
    {
        // Existing URL filters
        unset($filters['page']);
        $url = http_build_query($filters);

        $pages = ceil($total / $per_page);
        $start_from = ($page - 1) * $per_page + 1;

        ob_start();
        ?>
        <table class="pager_table">
            <tr>
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <td class="pager_table_td">
                        <a href="?<?= $url ?>&page=<?= $i ?>" class="pager_table_href<?= $page == $i ? '  pager_table_href_active' : '' ?>"><?= $i ?></a>
                    </td>
                <?php endfor; ?>
                <td class="pager_table_separator">&nbsp;</td>
                <td class="pager_table_td_lower nowrap"><?= $start_from ?> - <?= $start_from + $found - 1 ?> <?= w('pager_table_from') ?> <?= $total ?></td>
            </tr>
        </table>
        <?php

        return ob_get_clean();
    }
}