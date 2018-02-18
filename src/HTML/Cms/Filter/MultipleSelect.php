<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsMultipleSelect;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;


/**
 * Class MultipleSelect
 */
class MultipleSelect extends CmsMultipleSelect implements IFilter {
    protected $ignore_value;
    protected $ignore_in_sql_where = false;
    /**
     * @var Filter
     */
    private $filter;
    private $act_as = 'aui';

    /**
     * @param string $name
     * @param string $id
     */
    public function  __construct(string $name, string $id = '') {
        parent::__construct($name, $id);

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
     * @param string $value
     *
     * @return $this
     */
    public function ignoreValue($value)
    {
        $this->ignore_value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getActAs(): string
    {
        return $this->act_as;
    }

    /**
     * @param string $act_as
     *
     * @return $this
     */
    public function actAs($act_as) {
        $this->act_as = $act_as;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        $res = [];
        foreach ($this->selected as $value) {
            $res[] = $this->options[$value];
        }

        return implode(', ', $res);
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter {
        return $this->filter;
    }

    /**
     * @return mixed
     */
    public function getFilterValue()
    {
        return $this->selected;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !(bool)$this->selected;
    }

    /**
     * @return bool
     */
    public function loadData(): bool {
        $provider = $this->filter->getProvider();

        $res = false;
        if (isset($provider[$this->getName()])) {
            $this->setSelected($provider[$this->getName()]);
            $res = true;
        }

        return $res;
    }

    /**
     * @param array $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->filter->setProvider($provider);

        return $this;
    }
}
