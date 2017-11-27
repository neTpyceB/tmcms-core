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
    /**
     * Pagination, copy and modify where you need it
     * @param int $page
     * @param int $per_page
     * @param int $total_products
     * @param int $total_found_products_with_filters
     * @param array $filters
     * @return string
     */
    public static function renderPagination(int $page, int $per_page, int $total_products, int $total_found_products_with_filters, array $filters = []): string
    {
        // Remove "page" from existing URL filters because it is set in every href
        unset($filters['page']);
        $url = http_build_query($filters);

        $pages = ceil($total_found_products_with_filters / $per_page);

        // Found from - till
        $start_from = $page * $per_page + 1;
        $last_product_number = $start_from + $total_found_products_with_filters - 2;
        $reached_end = false;
        if ($last_product_number >= $total_found_products_with_filters) {
            $last_product_number = $total_found_products_with_filters;
            $reached_end = true;
        }
        ?>
        <div class="pagination">
            <?php if ($page > 0): ?>
                <a class="pagination__prev" href="?<?= $url ?>&page=<?= $page ?>"></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="pagination__link<?= $page === ($i - 1) ? ' active' : '' ?>" href="?<?= $url ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if (!$reached_end): ?>
                <a class="pagination__next" href="?<?= $url ?>&page=<?= $page + 2 ?>"></a>
            <?php endif; ?>
        </div>
        <?= $start_from ?> - <?= $last_product_number ?> <?= w('pager_table_from') ?> <?= $total_products ?>
        <?php

        return ob_get_clean();
    }
}
