<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsCheckboxList;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

class CheckboxList extends CmsCheckboxList implements IFilter
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
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->filter, $name)) {
            call_user_func_array([$this->filter, $name], $arguments);
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

        foreach ($this->checkboxes as $label => $checkbox) {
            /* @var $checkbox Checkbox */
            if ($checkbox->isChecked()) {
                $res[] = $label;
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
        return !$this->getFilterValue();
    }

    /**
     * @return array
     */
    public function getFilterValue(): array
    {
        return $this->getChecked();
    }

    /**
     * @return bool
     */
    public function loadData()
    {
        $provider = $this->filter->getProvider();

        if ($this->checkboxes) {
            foreach ($this->checkboxes as $checkbox) {
                /* @var $checkbox Checkbox */
                if (isset($provider[(string)$this->getName()]) && isset($provider[(string)$this->getName()][(string)$checkbox->getName()])) {
                    $checkbox->setChecked(true);
                }
            }

            return true;
        }

        return false;
    }
}