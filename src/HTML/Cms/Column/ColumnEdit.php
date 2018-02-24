<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

/**
 * Class ColumnEdit
 */
class ColumnEdit extends Column
{
    protected $width = '1%';

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance(string $key = 'edit')
    {
        $col = new self($key);
        $col->setTitle('');

        return $col;
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
        if (!$this->getValue()) {
            $this->allowHtml();
            $this->enableCenterAlign();
            $this->setValue('<i class="fa fa-pencil"></i>');
        }

        $value = $this->getCellData($row_data);
        if (!$value) {
            $value = '&mdash;';
        }

        return $this->getCellView($this->getHrefView($value, $this->getParsedHref($row_data, $linker, ['do' => 'edit', 'id' => $row_data['id']])), $row_data);
    }
}
