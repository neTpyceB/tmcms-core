<?php

namespace TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class SQLLimitParser
 */
class SQLLimitParser
{
    private $sql;
    private $offset;
    private $limit;

    /**
     * @param string $sql
     */
    public function __construct($sql)
    {
        $this->sql = $sql;
        $this->parse();
    }

    public function parse()
    {
        if (stripos($this->sql, 'offset') !== false) {
            list($this->limit, $this->offset) = preg_split('/offset/i', $this->sql);
            $this->offset = trim($this->offset);
            $this->limit = trim($this->limit);
        } elseif (isset($this->offset)) {
            list($this->offset, $this->limit) = explode(',', $this->sql);
            $this->offset = trim($this->offset);
            $this->limit = trim($this->limit);
            if ($this->limit == '') {
                $this->limit = $this->offset;
                $this->offset = NULL;
            }
        }
    }

    /**
     * @param int $offset
     * @return SQLLimitParser
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param int $limit
     * @return SQLLimitParser
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return string
     */
    public function toSQL()
    {
        return ($this->offset ? $this->offset . ', ' : '') . $this->limit;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toSQL();
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool)$this->limit;
    }
}
