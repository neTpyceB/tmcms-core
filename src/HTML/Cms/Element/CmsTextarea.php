<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Cms\HelperBox;
use TMCms\HTML\Element\Textarea;
use TMCms\Templates\Page;

\defined('INC') or exit;

/**
 * Class CmsTextarea
 * @package TMCms\HTML\Cms\Element
 */
class CmsTextarea extends Textarea {
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);

        $this->setRowCount(5);
        $this->addCssClass('form-control');
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '') {
        return new self($name, $value, $id);
    }


    /**
     * @return $this
     */
    public function disableBackupBlock() {
        return $this->setBackup(false);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        Page::getHead()->addJsURL('ckeditor/ckeditor.js');

        ob_start();

        ?><textarea data-provide="markdown" class="form-control" <?= $this->getCommonElementValidationAttributes() . $this->getAttributesString(array('value')) ?>><?= $this->getValue() ?></textarea><?php

        echo $this->getHelperbox();

        return ob_get_clean();
    }
}
