<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

/**
 * Class Select
 * @package TMCms\HTML\Cms\Filter
 */
class Select extends CmsSelect implements IFilter {
    private $act_as = 's';
    private $ignore_value = '';
    private $ignore_in_sql_where = false;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '') {
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
    public static function getInstance(string $name, string $value = '', string $id = '') {
        return new self($name, $value, $id);
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function ignoreValue($value) {
        $this->ignore_value = $value;
        return $this;
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
     *
     * @return bool
     */
    public function isIgnoreFilterInWhereSqlEnabled(): bool
    {
        return $this->ignore_in_sql_where;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter {
        return $this->filter;
    }

    /**
     * @return string
     */
    public function getActAs(): string
    {
        return strtolower($this->act_as);
    }

    /**
     * @param string $act_as
     *
     * @return $this
     */
    public function setActAs($act_as)
    {
        $this->act_as = $act_as;

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
    public function getValue(): string
    {
        $res = $this->getSelected();

        if ($res === (string)$this->ignore_value) {
            $res = '';
        }

        return $res;
    }

    /**
     * @return string
     */
    public function __toString() {
        if ($this->ignore_value && $this->getValue() === '') {
            $this->setSelected((string)$this->ignore_value);
        }

        return parent::__toString();
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        return $this->options[$this->getSelected()] ?? '';
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return $this->getValue() === '' || $this->getValue() === (string)$this->ignore_value;
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
     * @param string $data
     *
     * @return string
     */
    public function setValue($data)
    {
        parent::setValue($data);

        $this->setSelected((string)$data);

        return $this;
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

    /**
     * @param string $column
     * @return $this
     */
    public function setColumn($column)
    {
        $this->filter->setColumn($column);

        return $this;
    }
}
