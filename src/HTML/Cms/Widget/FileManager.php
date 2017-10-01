<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;

defined('INC') or exit;

/**
 * Class FileManager
 */
class FileManager extends Widget
{
    private $reload_page_after_modal_close = false;
    protected $value;
    protected $path = '/';
    protected $allowed_extensions = '';

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = NULL)
    {
        parent::__construct($owner);
    }

    /**
     * @param Element $owner
     *
     * @return $this
     */
    public static function getInstance(Element $owner = NULL)
    {
        return new self($owner);
    }

    /**
     * @return $this
     */
    public function enablePageReloadOnClose()
    {
        $this->reload_page_after_modal_close = true;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string $allowed_extensions
     *
     * @return $this
     */
    public function setAllowedExtensions(string $allowed_extensions)
    {
        $this->allowed_extensions = $allowed_extensions;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();
        ?>
        <input type="button"
               class="btn btn-info"
               value="<?= __('Filemanager') ?>"
               data-popup-url="?p=filemanager&nomenu&path=<?= $this->path ?>&allowed_extensions=<?= $this->allowed_extensions ?>&cache=<?= NOW ?>"
               data-popup-width="700"
               data-popup-height="720"
               data-popup-onclose="<?= $this->reload_page_after_modal_close ? 'reload' : '' ?>"
               data-popup-result-destination="#<?= $this->owner->getId() ?>">
        <?php
        return ob_get_clean();
    }
}