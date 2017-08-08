<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;

use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

class Text extends CmsInputText implements IFilter
{
    protected $helper = false;
    protected $column;
    protected $ignore_in_sql_where = false;
    private $act_as = 's';
    private $skip_left_match = false;
    private $skip_right_match = false;
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param        $name
     * @param string $value
     * @param string $id
     */
    public function __construct($name, $value = '', $id = '')
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
    public static function getInstance($name, $value = '', $id = NULL)
    {
        return new self($name, $value, $id);
    }

    public function enableActAsLike()
    {
        $this->actAs('like');

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Text
     */
    public function actAs($type): Text
    {
        $this->act_as = strtolower($type);

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
     * @return bool
     */
    public function isIgnoreFilterInWhereSqlEnabled(): bool
    {
        return $this->ignore_in_sql_where;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @return string
     */
    public function getActAs(): string
    {
        return $this->act_as;
    }

    /**
     * @param bool $flag
     *
     * @return Text
     */
    public function skipLeftMatch($flag = true): Text
    {
        $this->skip_left_match = $flag;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return Text
     */
    public function skipRightMatch($flag = true): Text
    {
        $this->skip_right_match = $flag;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterValue(): string
    {
        if ($this->act_as === 'like') {
            return ($this->skip_left_match ? '' : '%') . $this->getValue() . ($this->skip_right_match ? '' : '%');
        }

        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        $val = '';
        if ($this->act_as === 'like' && !$this->skip_left_match) {
            $val .= '*';
        }
        $val .= $this->getValue();
        if ($this->act_as === 'like' && !$this->skip_right_match) {
            $val .= '*';
        }

        return $val;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->getValue();
    }

    /**
     * @return bool
     */
    public function loadData(): bool
    {
        $provider = $this->filter->getProvider();
        $index_multi = $this->getName() . '_ids';
        if (isset($provider[$index_multi])) {
            $this->setValue($provider[$index_multi]);

            return false;
        }
        if (isset($provider[$this->getName()])) {
            $this->setValue($provider[$this->getName()]);

            return false;
        }

        return true;
    }

    /**
     * @param array $provider
     *
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->filter->setProvider($provider);

        return $this;
    }

    /**
     * @param string $column
     *
     * @return $this
     */
    public function setColumn($column)
    {
        $this->filter->setColumn($column);

        return $this;
    }
}