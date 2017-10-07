<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\Element\CmsInputDataList;
use TMCms\HTML\Cms\Filter;
use TMCms\HTML\Cms\IFilter;

class DataList extends CmsInputDataList implements IFilter {
    protected $helper = false;
    protected $ignore_in_sql_where = false;
    private $ignore_value;
    private $act_as = 's';
	private $skip_left_match = false;
	private $skip_right_match = false;
	/**
	 * @var Filter
	 */
	private $filter;

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
		parent::__construct($name, $value, $id);

		$this->filter = new Filter();
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @return $this
	 */
	public static function getInstance(string $name, string $value = '', string $id = '') {
		return new self($name, $value, $id);

	}

    /**
     * @return $this
     */
    public function enableActAsLike()
    {
        $this->actAs('like');

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function actAs(string $type)
    {
        $this->act_as = strtolower($type);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function ignoreValue(string $value)
    {
        $this->ignore_value = $value;

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
	 * @return Filter
	 */
	public function getFilter(): Filter {
		return $this->filter;
	}

	/**
	 * @return string
	 */
	public function getActAs() {
		return $this->act_as;
	}

	/**
	 * @param bool $flag
     *
	 * @return $this
	 */
	public function skipLeftMatch(bool $flag = true) {
		$this->skip_left_match = $flag;

		return $this;
	}

	/**
	 * @param bool $flag
     *
	 * @return $this
	 */
	public function skipRightMatch(bool $flag = true) {
		$this->skip_right_match = $flag;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFilterValue() {
        if ($this->act_as === 'like') {
            return ($this->skip_left_match ? '' : '%') . $this->getValue() . ($this->skip_right_match ? '' : '%');
        }

        return $this->getValue();
	}

	/**
	 * @return string
	 */
	public function getDisplayValue() {
        return $this->getValue();
	}

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getValue() === '' || $this->getValue() === $this->ignore_value || $this->isIgnoreFilterInWhereSqlEnabled();
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
     * @return bool
     */
    public function loadData(): bool {
		$provider = $this->filter->getProvider();

        if (isset($provider[$this->getName() . '_ids'])) {
            $this->setValue($provider[$this->getName() . '_ids']);
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