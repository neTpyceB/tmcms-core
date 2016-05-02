<?php

namespace TMCms\HTML\Cms\Column;

use TMCms\Config\Configuration;
use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\TableLinker;
use TMCms\Modules\Images\Entity\ImageEntityRepository;
use TMCms\Modules\ModuleManager;

/**
 * Class ColumnGallery
 */
class ColumnGallery extends Column {
	protected $width = '155';
	protected $images = [];

	/**
	 * @param string $key
	 * @return $this
	 */
	public static function getInstance($key = 'images') {
		if (!ModuleManager::moduleExists('images') || !ModuleManager::moduleExists('gallery')) {
			error('Module Gallery and module Images are required for ColumnGallery');
		}

		$res = new self($key);
		$res->allowHtml();

		return $res;
	}

	/**
	 * @param ImageEntityRepository $images
	 * @return $this
	 */
	public function setImages(ImageEntityRepository $images) {
		$images->addOrderByField();
		$image_data = [];

		foreach ($images->getAsArrayOfObjectData() as $image) {
			$image_data[$image['item_id']][] = $image['image'];
		}

		$this->images = $image_data;

		return $this;
	}

	/**
	 * @param $row
	 * @param array $row_data
	 * @param TableLinker $linker
	 * @return string
	 */
	public function getView($row, $row_data, TableLinker $linker) {
		$images = isset($this->images[$row_data['id']]) ? $this->images[$row_data['id']] : [];

		$value = $this->getCellData($row_data);

		$images_html = [];

		// Value is set?
		if (!$value) {
			// Draw images
			if ($images) {
				foreach ($images as $k => $image) {
					if ($k < 3) {
						// Draw miniature
						$images_html[] = '<img src="' . $image . '&resizefit=30x30&key=' . Configuration::getInstance()->get('cms')['unique_key'] . '" style="display: inline-block; vertical-align: top; margin: 0 .25rem .25rem 0; border-radius: 3px;">';
					} else {
						// Draw last step
						$images_html[] = '<div style="display: inline-block; vertical-align: top; text-align: center; height: 30px; min-width: 30px; padding: 0 .75rem; background: #EBEDF4; border-radius: 3px;"><span style="line-height: 30px; font-weight: 600">+' . (count($images) - $k) . '</span></div>';
						break;
					}
				}
			} else {
				$images_html[] = '<div style="display: inline-block; vertical-align: top; text-align: center; height: 30px; width: 30px; background: #EBEDF4; border-radius: 3px;"><span style="line-height: 30px;"><i class="fa fa-upload"></i></span></div>';
			}

			$value = implode('', $images_html);
		}

		$linker_array = array('do' => 'images', 'id' => $row_data['id']);
		$href = $this->getHref($row_data, $linker, $linker_array);

		$cell_view = $this->getHrefView($value, $href);

		return $this->getCellView($cell_view, $row_data);
	}
}