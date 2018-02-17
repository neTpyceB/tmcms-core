<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class RadioBox
 */
class RadioBox extends Element {
    /**
     * @var array
     */
    protected $radio_buttons = [];
    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @param string $name
     */
    public function  __construct($name) {
        parent::__construct();
        $this->setName($name);
    }

    /**
     * @return int
     */
    public function getSelected(): int
    {
        foreach ($this->radio_buttons as $id => $radio) {
            /* @var $radio InputRadio */
            if ($radio->isSelected()) {
                return (int)$id;
            }
        }

        return 0;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setSelected($id) {
        foreach ($this->radio_buttons as $k => $radio) {
            /* @var $radio InputRadio */
            if ((int)$k === (int)$id) {
                $radio->setSelected(true);
            } else {
                $radio->setSelected(false);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $res = [];

        foreach ($this->radio_buttons as $id => $radio) {
            /* @var $radio InputRadio */
            $res[$id] = $radio->getValue();
        }

        return $res;
    }

    /**
     * @return string
     */
    public function __toString() {
        ob_start();

        foreach ($this->getRadioButtons() as $id => $radio) {
            /* @var $radio InputRadio */
            /** @var $parent RadioBox */
            $parent = $radio->getParentRadioBox();
            if ($parent) {
                $radio->setValidatorAttributes($parent->getValidatorAttributes());
            }

            echo '<label style="display: block; margin-bottom: 1rem; font-weight: 400;">' . $radio->setOnchange($this->getOnchange()) . $this->labels[$id] . '</label>';
        }

        return ob_get_clean();
    }

    /**
     * @return array
     */
    public function getRadioButtons(): array
    {
        return $this->radio_buttons;
    }

    /**
     * @param array $radioButtons
     *
     * @return $this
     */
    public function setRadioButtons(array $radioButtons)
    {
        foreach ($radioButtons as $id => $value) {
            $this->radio_buttons[$id] = new InputRadio($this->getName(), $id, $id);
            $this->labels[$id] = $value;
        }

        return $this;
    }
}
