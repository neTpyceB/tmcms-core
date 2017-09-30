<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

defined('INC') or exit;

/**
 * Class Filter
 */
class Filter
{
    private $columns = [];
    private $provider;

    /**
     * @param string $column
     *
     * @return $this
     */
    public function setColumn($column)
    {
        $this->columns = (array)$column;
        $this->provider =& $_GET; // As reference

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function addColumns(array $columns)
    {
        $this->addColumn($columns);

        return $this;
    }

    /**
     * @param mixed $column
     *
     * @return $this
     */
    public function addColumn($column)
    {
        if (is_array($column)) {
            $this->columns = array_merge((array)$this->columns, $column);
        } else {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getColumn()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    function getFormattedColumn()
    {
        $res = [];

        foreach ($this->columns as $column) {
            if (strpos($column, '.') !== false) {
                list($alias, $column) = explode('.', $column);
                $res[] = '`' . $alias . '`.`' . $column . '`';
            } else {
                $res[] = '`' . $column . '`';
            }
        }

        return $res;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param array $provider
     *
     * @return $this
     */
    public function setProvider(array $provider)
    {
        $this->provider = $provider;

        return $this;
    }
}