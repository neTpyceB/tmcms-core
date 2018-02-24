<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

/**
 * Class ColumnActive
 */
class ColumnActive extends ColumnCheckbox
{
    protected $width = '1%';
    protected $align = 'center';
    protected $ajax = true;
    protected $onclick = '';

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key = 'active')
    {
        $col = new self($key);
        $col->enableOrderableColumn();

        return $col;
    }

    /**
     *
     * @param int    $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        // Auto set _href for update links
        if (!$this->getHref()) {
            $tmp = $_GET;
            $tmp['do'] = str_replace('_default', '', $tmp['do']);
            $tmp['do'] = ($tmp['do'] ? '_' : '') . $tmp['do'] . '_' . $this->key;
            $tmp['id'] = '{%id%}';

            $this->setHref(urldecode('?' . http_build_query($tmp)));
        }

        // Output
        $href = $this->getParsedHref($row_data, $linker, ['do' => 'active', 'id' => $row_data['id']]);
        $value = $this->getCellData($row_data);

        $cell_data = '
        <form action="' . $href . '" method="post">
            <input onclick="' . ($this->ajax ? 'checkbox_by_ajax(this)' : 'this.form.submit();'). '"
                type="' . ($this->radio ? 'radio' : 'checkbox'). '"
                ' . ($value ? ' checked="checked"' : ''). '
                name="input"
                class="noBorder"
                value="1"
            >
        </form>';

        return $this->getCellView($cell_data, $row_data);
    }
}
