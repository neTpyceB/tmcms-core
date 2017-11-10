<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class Select
 */
class Select extends Element
{
    protected $options = [];
    protected $disabled_options = [];
    protected $selected;
    protected $use_html_encode = true;
    protected $custom_styled = true;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ?: $name);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function setDisabledOptions(array $ids)
    {
        $this->disabled_options = $ids;

        return $this;
    }

    /**
     * @return array $disabled_options
     */
    public function getDisabledOptions(): array
    {
        return $this->disabled_options;
    }

    /**
     * @param array $ids
     *
     * @return $this
     */
    public function addDisabledOptions(array $ids)
    {
        $this->disabled_options = \array_merge($this->disabled_options, $ids);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
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

    /**
     * @return string
     */
    public function getSelected(): string
    {
        return $this->selected;
    }

    /**
     * @param string $selected
     *
     * @return $this
     */
    public function setSelected(string $selected)
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * @param string $value
     * @param string $text
     *
     * @return $this
     */
    public function addOption(string $value, string $text)
    {
        $this->options[$value] = $text;

        return $this;
    }

    /**
     * @return $this
     */
    public function allowHtml()
    {
        $this->use_html_encode = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableCustomStyled()
    {
        $this->custom_styled = false;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $attr = $this->getAttributesString();

        return '<select' . $this->getCommonElementValidationAttributes()
            . ($this->isFieldRequired() ? ' required' : '')
            . ' class="form-control ' . ($this->custom_styled ? 'chosen ' : '') . '"'
            . ($attr ? ' ' . $attr : '')
            . '>'
            . $this->getHtmlOptions() .
            '</select>';
    }

    /**
     * @return string
     */
    protected function getHtmlOptions(): string
    {
        $disabled_options = \array_flip($this->disabled_options);

        \ob_start();

        foreach ($this->options as $key => $option):
            $key = (string)$key;

            /** @var array $option */
            // Use optgroup with multidimensional array
            if (\is_array($option)): ?>
                <optgroup label="<?= $key ?>"><?= $key ?>
                    <?php foreach ($option as $option_key => $option_inner):
                        if ($this->use_html_encode) {
                            $option_key = \htmlspecialchars($option_key, \ENT_QUOTES);
                            $option_inner = \htmlspecialchars($option_inner);
                        }
                        ?>
                        <option value="<?= $option_key ?>"<?= ($this->isSelected($option_key) ? ' selected' : '')
                        . (isset($disabled_options[$option_key]) ? ' disabled="disabled"' : '') ?>>
                            <?= $option_inner ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php else:
                if ($this->use_html_encode) {
                /** @var string $option */
                    $key = \htmlspecialchars($key, \ENT_QUOTES);
                    $option = \htmlspecialchars($option);
                }
                ?>
                <option value="<?=$key?>"<?= ($this->isSelected($key) ? ' selected="selected"' : '')
                . (isset($disabled_options[$key]) ? ' disabled="disabled"' : '') ?>>
                    <?=$option?>
                </option>
            <?php endif;?>
        <?php endforeach;

        return \ob_get_clean();
    }

    /**
     * @param string $k
     *
     * @return bool
     */
    public function isSelected($k): bool
    {
        $res = (\is_array($this->selected) && \in_array($k, $this->selected, true)) || $k === $this->selected;

        return $res;
    }
}
