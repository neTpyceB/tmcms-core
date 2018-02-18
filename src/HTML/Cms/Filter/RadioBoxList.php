<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsRadioBox;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;
use TMCms\HTML\Element\InputRadio;

/**
 * Class RadioBoxList
 * @package TMCms\HTML\Cms\Filter
 */
class RadioBoxList extends CmsRadioBox implements IFilter
{

    protected $ignore_in_sql_where = false;
    private $act_as = 'aui';
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param string $name
     * @param string $id
     */
    public function __construct(string $name, string $id = '')
    {
        parent::__construct($id ?: $name);

        $this->filter = new Filter();
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
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call(string $name, array $arguments)
    {
        if (\method_exists($this->filter, $name)) {
            \call_user_func_array([$this->filter, $name], $arguments);
        } else {
            return parent::__call($name, $arguments);
        }

        return $this;
    }

    /**
     * Skip filter in SQL where query
     *
     * @return $this
     */
    public function enableIgnoreFilterInWhereSql()
    {
        $this->ignore_in_sql_where = true;

        return $this;
    }

    /**
     * Skip filter in SQL where query
     *
     * @return bool
     */
    public function isIgnoreFilterInWhereSqlEnabled(): bool
    {
        return $this->ignore_in_sql_where;
    }

    /**
     * @return string
     */
    public function getActAs(): string
    {
        return $this->act_as;
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        $res = [];

        foreach ($this->radio_buttons as $label => $radio) {
            /* @var $radio InputRadio */
            if ($radio->isSelected()) {
                $res[] = $this->labels[$label];
            }
        }

        return implode(', ', $res);
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !((bool)$this->getFilterValue());
    }

    /**
     * @return int
     */
    public function getFilterValue(): int
    {
        return $this->getSelected();
    }

    /**
     * @return bool
     */
    public function loadData(): bool
    {
        $default_select = $this->getSelected();

        $selected = false;
        $provider = $this->filter->getProvider();
        $res = false;
        foreach ($this->radio_buttons as $label => $radio) {
            /* @var InputRadio $radio */
            if (isset($provider[$this->getName()]) && ((string)$provider[$this->getName()] === (string)$radio->getValue())) {
                $selected = true;
                $radio->setSelected(true);
            } else {
                $radio->setSelected(false);
            }
        }

        if ($default_select !== null && !$selected) {
            $this->setSelected($default_select);
            $res = true;
        }

        return $res;
    }
}
