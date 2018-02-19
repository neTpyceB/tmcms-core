<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputRadio;
use TMCms\HTML\Element\RadioBox;

\defined('INC') or exit;

/**
 * Class CmsRadioBox
 * @package TMCms\HTML\Cms\Element
 */
class CmsRadioBox extends RadioBox {
    /**
     * @param string $name
     *
     * @return $this
     */
    public static function getInstance(string $name) {
        return new self($name);
    }

    /**
     * @param array $radioButtons
     *
     * @return $this
     */
    public function setRadioButtons(array $radioButtons) {
        foreach ($radioButtons as $id => $value) {
            $r = new InputRadio($this->getName(), $id, $id);
            $r->setParentRadioBox($this);

            $this->radio_buttons[$id] = $r;
            $this->labels[$id] = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstElementId(): string
    {
        foreach ($this->radio_buttons as $id => $radio) {
            return (string)$id;
        }

        return '';
    }
}
