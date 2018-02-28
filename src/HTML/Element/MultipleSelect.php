<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class MultipleSelect
 * @package TMCms\HTML\Element
 */
class MultipleSelect extends Element
{
    protected $options = [];
    protected $selected = [];

    protected $custom_styled = true;

    /**
     * @param string $name
     * @param string $id
     */
    public function __construct(string $name, string $id = '')
    {
        parent::__construct();

        $this->setName($name);
        $this->setId($id ?: $name);
        $this->enableMultiple();
    }

    /**
     * @param string $name
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $id = '')
    {
        return new self($name, $id);
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
     * @return array
     */
    public function getSelected(): array
    {
        return $this->selected;
    }

    /**
     * @param array $selected
     *
     * @return $this
     */
    public function setSelected($selected)
    {
        $this->selected = (array)$selected;

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
        ob_start();

        $name = $this->getName();

        if ($this->custom_styled) {
            $this->addCssClass('chosen');
        }

        $this->setName($name . '[]');

        ?><select <?= $this->getCommonElementValidationAttributes() . $this->getAttributesString() ?>>
        <?php foreach ($this->options as $key => $option):

            // Use optgroup with multidimensional array?
            if (\is_array($option)): ?>
                <optgroup label="<?= $key ?>"><?= $key ?>
                    <?php foreach ($option as $option_key => $option_inner): ?>
                        <option value="<?= $option_key ?>"<?= $this->isSelected($option_key) ? ' selected' : '' ?>><?= $option_inner ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php else: ?>
                <option value="<?= $key ?>"<?= $this->isSelected($key) ? ' selected="selected"' : '' ?>><?= $option ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
        <input type="hidden" name="<?= $this->getId() ?>_ordered" id="<?= $this->getId() ?>_ordered"
               value="<?= implode(',', $this->selected) ?>">
        <script>
            $(function () {
                setTimeout(function () {
                    if (typeof($('#<?= $this->getId() ?>').setSelectionOrder) == 'function') {
                        $('#<?= $this->getId() ?>').setSelectionOrder(<?= json_encode($this->selected, JSON_OBJECT_AS_ARRAY) ?>);
                    }
                }, 1000);

            });
        </script><?php // Hidden require for values in correct order for plugin Chosen

        $this->setName($name);

        return ob_get_clean();
    }

    /**
     * @param int $key
     *
     * @return bool
     */
    public function isSelected($key): bool
    {
        return \in_array($key, $this->selected, true);
    }
}
