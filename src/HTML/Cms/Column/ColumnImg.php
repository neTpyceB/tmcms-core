<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use TMCms\Config\Configuration;
use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

/**
 * Class ColumnImg
 */
class ColumnImg extends Column {
    private $img_width;
    private $img_height;

    protected $width = '100';
    protected $height;

    /**
     * @param string $key
     * @return $this
     */
    public static function getInstance(string $key = 'image') {
        return new self($key);
    }

    public function getView(int $row, array $row_data, Linker $linker): string
    {
        $img_src = $this->getCellData($row_data);

        if ($img_src) {
            $width = $this->width;
            $height = $this->height;

            if (!$width && !$height && is_file(DIR_BASE . $img_src) && ($img = @getimagesize(DIR_BASE . $img_src))) {
                list($img_w, $img_h) = $img;

                if (($img_w / $this->width) > ($img_h / $this->height)) {
                    $width = $this->width;
                } else {
                    $height = $this->height;
                }
            }
        } else {
            $width = $height = NULL;
        }

        if ($img_src && ($width || $height)) {
            $cell_data = '<img ' . ($width ? ' width="' . $width . '"' : '') . ($height ? ' height="' . $height . '"' : '') . 'src="' . $img_src . '&resize=100x70&key=' . Configuration::getInstance()->get('cms')['unique_key'] . '" alt="">';
        } else {
            $cell_data = '';
        }

        if ($this->getHref()){
            $cell_data = $this->getHrefView($cell_data, $this->getParsedHref($row_data, $linker));
        }

        return $this->getCellView($cell_data, $row_data);
    }
}
