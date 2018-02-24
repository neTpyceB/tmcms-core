<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use InvalidArgumentException;
use RuntimeException;

\defined('INC') or exit;

/**
 * Class Dashboard
 * @package TMCms\HTML\Cms
 */
class Dashboard
{
    /**
     * @var string
     */
    private $table_id;

    /**
     * @var string
     */
    private $table_title;
    /**
     * @var string
     */
    private $width = '100%';
    /**
     * @var
     */
    private $border;
    /**
     * @var array
     */
    private $structure = [];
    private $columns = 0;
    private $rows = 0,

        $cell_example = [
            'value' => '',
            'props' => []
        ];
    /**
     * @var array
     */
    private $col_props = [];

    /**
     * @param string $table_id
     * @param int $cols
     * @param int $rows
     * @param string $table_title
     */
    public function __construct(string $table_id, int $cols = 0, int $rows = 0, $table_title = '')
    {
        $this->setTableId($table_id);

        if ($rows) {
            $this->setRows($rows);
        }

        if ($cols) {
            $this->setColumns($cols);
        }

        $this->table_title = $table_title;
    }

    /**
     * @param int $amount
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setRows(int $amount)
    {
        if (!$amount) {
            throw new InvalidArgumentException('Rows amount must be set');
        }

        if ($this->rows === $amount) {
            return $this;
        }

        if ($this->rows < $amount) {
            // Add rows
            for ($i = $amount - $this->rows; $i > 0; $i--) {
                $this->structure[] = $this->columns ? array_fill(0, $this->columns, $this->cell_example) : [];
            }
        } else {
            // Remove rows from the end
            $this->structure = \array_slice($this->structure, 0, $amount);
        }

        $this->rows = $amount;

        return $this;
    }

    /**
     * @param int $amount
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setColumns(int $amount)
    {
        if (!$amount) {
            throw new InvalidArgumentException('Columns amount must be set');
        }

        if ($this->columns === $amount) {
            return $this;
        }

        foreach ($this->structure as &$v) {
            if ($this->columns < $amount) {
                // Add rows
                $v += $amount ? array_fill(0, $amount, $this->cell_example) : [];
            } else {
                // Remove rows from the end
                $v = \array_slice($v, 0, $amount);
            }
        }
        unset($v);

        $this->columns = $amount;

        return $this;
    }

    /**
     * @param $table_id
     * @param int $cols
     * @param int $rows
     *
     * @return $this
     */
    public static function getInstance(string $table_id, int $cols = 0, int $rows = 0, $table_title = '')
    {
        return new self($table_id, $cols, $rows, $table_title);
    }

    /**
     * @return string
     */
    public function getTableId(): string
    {
        return $this->table_id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setTableId($id)
    {
        $this->table_id = $id;

        return $this;
    }

    /**
     * @param string $width
     *
     * @return $this
     */
    public function setTableWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getTableWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $border
     *
     * @return $this
     */
    public function setTableBorder($border)
    {
        $this->border = (int)$border;

        return $this;
    }

    /**
     * @return string
     */
    public function getTableBorder(): string
    {
        return $this->border;
    }

    /**
     * @return int
     */
    public function getColumns(): int
    {
        return $this->columns;
    }

    /**
     * @return int
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    /**
     * @param int $col
     * @param int $row
     * @param string $value
     *
     * @return $this
     */
    public function setCellValue(int $col, int $row, string $value, string $ajax_url = '')
    {
        if (!isset($this->structure[$row][$col])) {
            return $this;
        }

        // Show loader
        if (!$value && $ajax_url) {
            $value = '<img src="'. DIR_CMS_IMAGES_URL .'loading.gif">';
        }

        $this->structure[$row][$col]['value'] = $value;
        $this->structure[$row][$col]['ajax_url'] = $ajax_url;

        return $this;
    }

    /**
     * @param int $col
     * @param int $row
     *
     * @return string
     */
    public function getCellValue(int $col, int $row): string
    {
        if (!isset($this->structure[$row][$col])) {
            return '';
        }

        return $this->structure[$row][$col]['value'];
    }

    /**
     * @param int $col
     * @param int $row
     * @param array $props
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCellProperties(int $col, int $row, array $props)
    {
        if (!isset($this->structure[$row][$col])) {
            return $this;
        }

        if (isset($props['width'])) {
            if (substr($props['width'], -1) !== '%') {
                throw new InvalidArgumentException('Cell width should be set as percentage, but you supplied "' . $props['width'] . '".');
            }

            if (!isset($this->col_props[$col])) {
                $this->col_props[$col] = [];
            }

            $this->col_props[$col]['width'] = $props['width'];
            unset($props['width']);
        }

        $this->structure[$row][$col]['props'] = array_merge($props, $this->structure[$row][$col]['props']);

        return $this;
    }

    /**
     * @param int $col
     * @param int $row
     * @return string
     */
    public function getCellProperties($col, $row): string
    {
        if (!isset($this->structure[$row][$col])) {
            return '';
        }

        return $this->structure[$row][$col]['props'];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->table_id) {
            throw new RuntimeException('Missing table ID.');
        }

        if (\count($this->col_props) !== $this->columns) {
            throw new RuntimeException('All columns should have properties set.');
        }

        foreach ($this->col_props as $i => &$v) {
            if (!isset($v['width'])) {
                throw new RuntimeException('All columns should have width set. Column "' . $i . '" does not have width.');
            }
        }
        unset($i, $v);

        $html_table = ' width="' . $this->width . '"';

        if ($this->border) {
            $html_table .= ' border="' . $this->border . '"';
        }
        if ($this->table_id) {
            $html_table .= ' id="' . htmlspecialchars($this->table_id, ENT_QUOTES) . '"';
        }

        ob_start();

        ?>
        <div class="portlet box<?= $this->table_title ? ' green' : '' ?>">
            <?php if ($this->table_title): ?>
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-gift"></i><?= $this->table_title ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="portlet-body">
                <table class="dashboard_table"<?= $html_table ?>>
                    <colgroup>
                        <?php foreach ($this->col_props as $v) : ?>
                            <col width="<?= $v['width'] ?>">
                        <?php endforeach; ?>
                    </colgroup>

                    <?php foreach ($this->structure as $row_id => $rows): ?>
                        <tr>
                            <?php foreach ($rows as $cell_id => $cell):
                                $dashboard_id = isset($cell['ajax_url']) && $cell['ajax_url'] ? md5($cell['ajax_url']) : $row_id . '_' . $row_id; ?>
                                <td data-dashboard-id="<?= $dashboard_id ?>"
                                    valign="<?= $cell['props']['valign'] ?? 'top' ?>"<?= isset($cell['props']['height']) ? ' height="' . $cell['props']['height'] . '"' : '' ?><?= isset($cell['props']['align']) ? ' align="' . $cell['props']['align'] . '"' : '' ?><?= isset($cell['props']['id']) ? ' id="' . $cell['props']['id'] . '"' : '' ?>><?= $cell['value'] ?></td>
                                <?php if (!empty($cell['ajax_url'])): ?>
                                    <script>
                                        (function dashboard_worker_<?=$dashboard_id?>() {
                                            $.ajax({
                                                url: '<?= $cell['ajax_url'] ?>&ajax',
                                                success: function (data) {
                                                    $('td[data-dashboard-id="<?= $dashboard_id ?>"]').html(data);
                                                },
                                                complete: function () {
                                                    // Schedule the next request when the current one's complete
                                                    setTimeout(dashboard_worker_<?=$dashboard_id?>, 30000); // Half a minute
                                                }
                                            });
                                        })();
                                    </script>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }
}
