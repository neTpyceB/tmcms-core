<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

/**
 * Class ColumnDelete
 */
class ColumnDelete extends Column {
    protected $width = '1%';
    protected $align = 'center';
    protected $href_confirm = true;

    /**
     *
     * @param string $key
     * @return ColumnDelete
     */
    public static function getInstance($key = 'delete') {
        $col = new self($key);
        $col->setTitle('');

        return $col;
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
        if (!$this->getHref()) {
            $tmp = $_GET;
            $tmp['do'] = str_replace('_default', '', $tmp['do']);
            $tmp['do'] = ($tmp['do'] ? '_' : '') . $tmp['do'] . '_delete';
            $tmp['id'] = '{%id%}';
            $this->setHref(urldecode('?' . http_build_query($tmp)));
        }

        $value = $this->getCellData($row_data);

        if (!$value) {
            $value = '<i class="fa fa-trash-o"></i>';
        }

        $cell_view = $this->getHrefView($value, $this->getParsedHref($row_data, $linker, ['do' => '_delete', 'id' => $row_data['id']]));

        return $this->getCellView($cell_view, $row_data);
    }
}
