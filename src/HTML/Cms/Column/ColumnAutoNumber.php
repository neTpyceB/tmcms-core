<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

class ColumnAutoNumber extends Column
{
    private $start_from = 1;
    private $order_asc = true;
    private $row_offset = 0;
    private $current_offset = 0;

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key)
    {
        return new self($key);
    }

    /**
     * @param int $row
     *
     * @return $this
     */
    public function setRowOffset(int $row)
    {
        $this->row_offset = $row;

        return $this;
    }

    /**
     * @return $this
     */
    public function setOrderAsc()
    {
        $this->order_asc = true;

        return $this;
    }

    /**
     * @return ColumnAutoNumber
     */
    public function setOrderDesc()
    {
        $this->order_asc = false;

        return $this;
    }

    /**
     * @param int $from
     *
     * @return $this
     */
    public function setStartFrom(int $from)
    {
        $this->start_from = $from;

        return $this;
    }

    /**
     * @param int    $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        if ($this->order_asc) {
            $cell_view = $this->start_from + $this->row_offset + $this->current_offset;
        } else {
            $cell_view = $this->start_from - $this->row_offset - $this->current_offset;
        }

        if ($this->href) {
            $cell_view = $this->getHrefView($cell_view, $this->getParsedHref($row_data, $linker));
        }

        $this->current_offset++;

        return $this->getCellView($cell_view, $row_data);
    }
}
