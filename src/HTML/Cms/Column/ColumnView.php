<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

\defined('INC') or exit;

/**
 * Class ColumnView
 */
class ColumnView extends Column
{
    protected $width = '1%';

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key = 'view')
    {
        $column = new self($key);
        $column->setTitle('');

        return $column;
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
        if (!$this->getValue()) {
            $this->allowHtml();
            $this->enableCenterAlign();
            $this->setValue('<i class="fa fa-eye"></i>');
        }

        $value = $this->getCellData($row_data);

        if (!$value) {
            $value = '&mdash;';
        }

        $linker_array = [
            'do' => 'view',
            'id' => $row_data['id']
        ];

        $href = $this->getParsedHref($row_data, $linker, $linker_array);
        $cell_view = $this->getHrefView($value, $href);

        return $this->getCellView($cell_view, $row_data);
    }
}
