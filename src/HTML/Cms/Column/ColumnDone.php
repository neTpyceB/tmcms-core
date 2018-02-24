<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

class ColumnDone extends Column {
    protected $width = '1%';
    protected $align = 'center';

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key) {
        return new self($key);
    }

    /**
     * @param int $row
     * @param array array $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        $value = $this->getCellData($row_data);
        $cell_data = '<span style="color: '. ($value ? 'green' : 'red') .'">●</span>';

        return $this->getCellView($cell_data, $row_data);
    }
}
