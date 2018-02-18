<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

/**
 * Class Html
 */
class Html extends CmsHtml implements IFilter
{
    protected $ignore_in_sql_where = false;

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
        parent::__construct($name);

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
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
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
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     */
    public function __call(string $name, array $arguments)
    {
        // Call to magic method
        if (!method_exists($this->filter, $name)) {
            return parent::__call($name, $arguments);
        }

        call_user_func_array([$this->filter, $name], $arguments);

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterValue(): string
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        return $this->getValue();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->getValue();
    }

    public function getActAs()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function loadData(): bool
    {
        return false;
    }
}
