<?php

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

/**
 * Class ColumnAccept
 */
class ColumnAccept extends Column
{
    protected $width = '1%';
    protected $align = 'center';

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance($key)
    {
        return new self($key);
    }

    /**
     * @param int  $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker)
    {
        $linker_array = ['do' => '_accept', 'id' => $row_data['id']];
        $value = $this->getCellData($row_data);

        $cell_data = '<form action="' . $this->getHref($row_data, $linker, $linker_array) . '" method="post"><input onclick="this.form.submit();" type="checkbox"' . ($value ? ' checked="checked" disabled="disabled"' : '') . ' name="input" value="1"></form>';

        return $this->getCellView($cell_data, $row_data);
    }
}