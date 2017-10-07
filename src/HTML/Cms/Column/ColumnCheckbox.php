<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

defined('INC') or exit;

/**
 * Class ColumnCheckbox
 */
class ColumnCheckbox extends Column
{
    protected $width = '1%';
    protected $align = 'center';
    protected $radio = false;
    protected $onclick = 'this.form.submit();';

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
     * @param string $onclick
     *
     * @return $this
     */
    public function setOnclick(string $onclick)
    {
        $this->onclick = $onclick;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnclick(): string
    {
        return $this->onclick;
    }

    /**
     * @param int    $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker)
    {
        $this->addDataAttribute('id', $row_data['id']);

        $value = $this->getCellData($row_data);

        $cell_data = '<form action="' . $this->getHref($row_data, $linker) . '" method="post">';
        $cell_data .= '<input ' . $this->getDataAttributesHtml() . ' onclick="' . $this->onclick . '" type="' . ($this->radio ? 'radio' : 'checkbox') . '"' . ($value ? ' checked="checked"' : '') . ' name="input" value="' . $value . '"></form>';

        return $this->getCellView($cell_data, $row_data);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }
}