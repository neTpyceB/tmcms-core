<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

\defined('INC') or exit;

/**
 * Class ColumnRadio
 */
class ColumnRadio extends Column {
    protected $width = '1%';
    protected $align = 'center';
    protected $onclick = 'this.form.submit();';

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
     * @param array $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        $value = $this->getCellData($row_data);
        $cell_data = '<form action="'. $this->getParsedHref($row_data, $linker) .'" method="post"><input onclick="'. ($this->onclick ?: 'this.form.submit();') .'" type="radio"'. ($value ? ' checked="checked"' : '') .' name="input" class="noBorder" value="'. htmlspecialchars($value, ENT_QUOTES) .'"></form>';

        return $this->getCellView($cell_data, $row_data);
    }
}
