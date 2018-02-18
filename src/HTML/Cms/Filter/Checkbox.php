<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

/**
 * @method Checkbox setColumn()
 */
class Checkbox extends CmsCheckbox implements IFilter
{
    protected $ignore_in_sql_where = false;
    private $act_as = 'ui';
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct($name, $value, $id);

        $this->filter = new Filter();
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
    public function isIgnoreFilterInWhereSqlEnabled()
    {
        return $this->ignore_in_sql_where;
    }

    /**
     * @return string
     */
    public function getActAs()
    {
        return strtolower($this->act_as);
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
    public function getFilterValue(): bool
    {
        return (bool)$this->getValue();
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setValue(string $data)
    {
        parent::setValue($data);

        $this->setChecked(true);

        return $this;
    }

    /**
     * @return string or null
     */
    public function getDisplayValue(): string
    {
        return $this->checked ? 'on' : '';
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->isChecked();
    }

    /**
     * @return bool
     */
    public function loadData(): bool
    {
        $provider = $this->filter->getProvider();

        $res = false;
        if (isset($provider[$this->getName()])) {
            $this->setValue($provider[$this->getName()]);

            $res = true;
        }

        return $res;
    }
}
