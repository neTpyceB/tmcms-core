<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsInputHidden;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

defined('INC') or exit;

class Hidden extends CmsInputHidden implements IFilter
{
    protected $ignore_in_sql_where = false;
    private $act_as = 's';
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param string       $name
     * @param string|array $value
     * @param string       $id
     */
    public function __construct(string $name, $value = '', string $id = '')
    {
        parent::__construct($name, $value, $id);

        $this->filter = new Filter();
    }

    /**
     * @param string       $name
     * @param string $value
     * @param string       $id
     *
     * @return Hidden
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
     * @return $this
     */
    public function enableIgnoreFilterInWhereSql()
    {
        $this->ignore_in_sql_where = true;

        return $this;
    }

    /**
     * Skip filter in SQL where query
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
     * @return string
     */
    public function getFilterValue()
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getDisplayValue()
    {
        return $this->getValue();
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return (bool)!$this->getValue();
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

    /**
     * @param array $provider
     *
     * @return $this
     */
    public function setProvider(array $provider)
    {
        $this->filter->setProvider($provider);

        return $this;
    }

    public function getProvider()
    {
        return $this->filter->getProvider();
    }
}
