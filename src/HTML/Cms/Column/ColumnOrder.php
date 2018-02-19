<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;
use TMCms\Templates\PageHead;

\defined('INC') or exit;

/**
 * Class ColumnOrder
 */
class ColumnOrder extends Column
{
    private static $is_dragable = false;
    private static $dnd_id = 'id';
    private static $dnd_name = '_dnd_move';
    protected $width = '1%';
    protected $align = 'center';
    protected $nowrap = true;
    private $category_key;
    private $row_offset = 0;
    private $row_count = 0;
    private $current_offset = 0;

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key = 'order')
    {
        $col = new self($key);
        $col->setTitle('');

        return $col;
    }

    /**
     * @param string $table_id
     */
    public static function getDndJS(string $table_id = '')
    {
        ?>
        <style>
            table tbody td[data-column_key="order"]:hover {
                cursor: ns-resize;
            }

            table tbody tr {
                transition: opacity 0.2s ease-in-out;
            }

            table.sorting-table tbody tr.sorting-row {
                cursor: ns-resize;
                background: #eaeaf2;
            }

            table.sorting-table tbody tr.sorting-row td {
                border: 1px solid rgba(227, 230, 243, 0.6);
                border-right: 0;
                border-bottom: 0;
            }

            table.sorting-table tbody tr.sorting-row td:first-child {
                border-left: 0;
            }

            table.sorting-table tbody tr:not(.sorting-row) {
                opacity: 0.4;
            }
        </style>
        <script>
            $(<?= $table_id ?>).rowSorter({
                handler: '[data-column_key="order"]',

                onDrop: function (tbody, tr, new_index, old_index) {
                    // Create jQuery object for table body and get selected row's order in database
                    var $tbody = $('#<?= $table_id ?>').find('tbody');
                    var $tr = $tbody.children().eq(new_index);

                    // Getting move direction, retrieving old order and creating variable to store new order
                    var direct = new_index > old_index ? 'down' : 'up',
                        old_order = $tr.find('[data-column_key="order"]').attr('data-order'),
                        new_order;

                    // Retrieve new order, which is previous or next item's order (relative to new position in table)
                    if (direct == 'down') {
                        new_order = $tr.prev().find('[data-column_key="order"]').attr('data-order');
                    } else {
                        new_order = $tr.next().find('[data-column_key="order"]').attr('data-order');
                    }

                    // Loop through the set of tbody's descendants
                    setTimeout(function () {
                        $tbody.children().each(function (index) {
                            // Get current row's 'order' column
                            var order_column = $(this).find('td[data-column_key="order"]'),
                                order_column_href = order_column.find('a').attr('href').replace(/&direct=[^&]+/, '');

                            // Set appropriate arrows
                            if (index == 0) {
                                order_column.html('<a href="' + order_column_href + '&direct=down" class="nounderline dnd"><i class="fa fa-long-arrow-down"></i></a>');
                            } else if (index == $tbody.children().length - 1) {
                                order_column.html('<a href="' + order_column_href + '&direct=up" class="nounderline dnd"><i class="fa fa-long-arrow-up"></i></a>');
                            } else {
                                order_column.html(
                                    '<a href="' + order_column_href + '&direct=up" class="nounderline dnd"><i class="fa fa-long-arrow-up"></i></a>' +
                                    '&nbsp;&nbsp;&nbsp;' +
                                    '<a href="' + order_column_href + '&direct=down" class="nounderline dnd"><i class="fa fa-long-arrow-down"></i></a>'
                                );
                            }

                            // Save changes to database by sending an AJAX request
                            if (index == new_index) {
                                $.post(order_column_href + '&direct=' + direct + '&step=' + Math.abs(old_order - new_order) + '&ajax');
                            }
                        });
                    }, 100);
                }
            });
        </script><?php
    }

    /**
     * Set current row offset
     *
     * @param int $row
     *
     * @return $this
     */
    public function rowOffset(int $row)
    {
        $this->row_offset = abs($row);

        return $this;
    }

    /**
     * @param int $row
     *
     * @return $this
     */
    public function setCurrentOffset(int $row)
    {
        $this->current_offset = $row;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setCategoryKey(string $key)
    {
        $this->category_key = $key;

        return $this;
    }

    /**
     * Set total rows count
     *
     * @param int $cnt
     *
     * @return $this
     */
    public function rowCount(int $cnt)
    {
        $this->row_count = abs($cnt) - 1;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDragable(): bool
    {
        return self::$is_dragable;
    }

    /**
     * @return $this
     */
    public function disableDragable()
    {
        self::$is_dragable = false;

        return $this;
    }

    /**
     * @param string $id_field_name
     * @param string $action_name
     *
     * @return $this
     */
    public function enableDraggable($id_field_name = 'id', $action_name = '_dnd_move')
    {
        self::$is_dragable = true;

        self::$dnd_id = $id_field_name;
        self::$dnd_name = $action_name;

        PageHead::getInstance()->addJsUrl('plugins/jquery.rowsorter.js');

        return $this;
    }

    /**
     * @param int $row
     * @param array $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        // Auto set _href for update links
        if (!$this->href()) {
            $tmp = $_GET;
            $tmp['do'] = str_replace('_default', '', $tmp['do']);
            $tmp['do'] = ($tmp['do'] ? '_' : '') . $tmp['do'] . '_order';
            $tmp['id'] = '{%id%}';
            $this->href(urldecode('?' . http_build_query($tmp)));
        }

        // Output
        $linker_array = ['do' => '_order', 'id' => $row_data['id']];
        if ($this->category_key !== null) {
            $linker_array[$this->category_key] = $row_data[$this->category_key] ?? '';
        }

        if ($this->href) {
            $up_link = $this->getHref($row_data, $linker) . '&direct=up';
            $down_link = $this->getHref($row_data, $linker) . '&direct=down';
        } else {
            $up_link = $this->getHref($row_data, $linker, $linker_array + ['direct' => 'up']);
            $down_link = $this->getHref($row_data, $linker, $linker_array + ['direct' => 'down']);
        }

        $current_row = $this->row_offset + $this->current_offset;
        if ($this->row_count >= 1) {
            if ($current_row === 0) $cell_data = '<a href="' . $down_link . '" class="nounderline dnd"><i class="fa fa-long-arrow-down"></i></a>';
            elseif ($current_row === $this->row_count) $cell_data = '<a href="' . $up_link . '" class="nounderline dnd"><i class="fa fa-long-arrow-up"></i></a>';
            else $cell_data = '<a href="' . $up_link . '" class="nounderline dnd"><i class="fa fa-long-arrow-up"></i></a>&nbsp;&nbsp;&nbsp;<a href="' . $down_link . '" class="nounderline dnd"><i class="fa fa-long-arrow-down"></i></a>';
        } else {
            $cell_data = '&nbsp;&nbsp;&nbsp;';
        }
        $this->current_offset++;

        return $this->getCellView($cell_data, $row_data);
    }
}
