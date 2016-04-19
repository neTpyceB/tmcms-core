<?php

namespace TMCms\DB;

use \PDO;

defined('INC') or exit;

/**
 * Class TableTree
 */
class TableTree
{
    private
        $table, $order_column, $title_column, $columns,
        $id_column = 'id',
        $pid_column = 'pid',
        $translation_columns = [],
        $skip_ids = [];

    /**
     * @param $table
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function setSkipIds(array $ids)
    {
        $this->skip_ids = $ids;

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public static function getInstance($table)
    {
        return new self($table);
    }

    /**
     * @param string|array $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        if (is_array($columns)) {
            $this->columns = $columns ? '`t`.`' . implode('`, `t`.`', $columns) . '`' : '';
        } elseif (is_string($columns)) {
            $this->columns = $columns;
        }

        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setIDcolumn($name)
    {
        $this->id_column = $name;

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitleColumn($title)
    {
        $this->title_column = $title;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setPIDcolumn($name)
    {
        $this->pid_column = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setOrderColumn($name)
    {
        $this->order_column = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function addTranslationColumn($name)
    {
        $this->translation_columns[] = $name;

        return $this;
    }

    /**
     * @param int $pid
     * @return array|bool
     */
    public function getAsTree($pid = 0)
    {
        $pid = (int)abs($pid);

        $res = [];
        $qh = q($this->getSQL($this->columns, '`t`.`' . $this->pid_column . '`="' . $pid . '"'));

        // Recursive with children
        while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
            $q['children'] = $this->getAsTree($q[$this->id_column]);
            $res[$q[$this->id_column]] = $q;
        }

        return $res;
    }

    /**
     * @param int $pid
     */
    public function previewSQL($pid = 0)
    {
        dump(
            $this->getSQL($this->columns, '`t`.`' . $this->pid_column . '`="' . $pid . '"' . ($this->skip_ids ? ' AND `t`.`' . $this->id_column . '` NOT IN ("' . implode('", "', $this->skip_ids) . '")' : NULL)),
            'sql'
        );
    }

    /**
     * @param string $select
     * @param string $where
     * @param string $group_by
     * @return string
     */
    public function getSQL($select = NULL, $where = NULL, $group_by = NULL)
    {
        // Select all
        if (!isset($select)) {
            $select = '`t`.*';
        }

        $sql = 'SELECT ' . $select;
        $join_sql = '';

        foreach ($this->translation_columns as $i => $column) {
            $sql .= ', `d' . $i . '`.`' . LNG . '` AS `' . $column . '` ';
            $join_sql .= 'LEFT JOIN `cms_translations` AS `d' . $i . '` ON `t`.`' . $column . '`=`d' . $i . '`.`' . $this->id_column . '` ';
        }

        $sql .= "\n" . 'FROM `' . $this->table . '` AS `t`' . "\n";

        // Compile full SQL string
        if ($join_sql) {
            $sql .= $join_sql . "\n";
        }
        if (isset($where)) {
            $sql .= 'WHERE ' . $where . "\n";
        }
        if (isset($group_by)) {
            $sql .= 'GROUP BY ' . $group_by . "\n";
        }
        if (isset($this->order_column)) {
            $sql .= 'ORDER BY `t`.`' . $this->order_column . '`' . "\n";
        }

        return $sql;
    }

    /**
     * @param int $pid
     * @param int $lvl
     * @return array
     */
    public function getDataAsArray($pid = 0, $lvl = 0)
    {
        if (!ctype_digit((string)$pid)) {
            trigger_error('$pid should be an integer.');
        }
        if (!ctype_digit((string)$lvl)) {
            trigger_error('$lvl should be an integer.');
        }

        $res = [];
        $qh = q($this->getSQL($this->columns, '`t`.`' . $this->pid_column . '`="' . $pid . '"' . ($this->skip_ids ? ' AND `t`.`' . $this->id_column . '` NOT IN ("' . implode('", "', $this->skip_ids) . '")' : NULL)));

        // Recursive calls and combine in one-dimensional array
        while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
            $q['level'] = $lvl;
            $res[$q[$this->id_column]] = $q;
            $res += $this->getDataAsArray($q[$this->id_column], $lvl + 1);
        }

        return $res;
    }

    /**
     * @param int $pid
     * @param int $repeat
     * @param string $spacer
     * @param int $lvl
     * @return array
     */
    public function getAsArray4Options($pid = 0, $repeat = 3, $spacer = '&nbsp;', $lvl = 0)
    {
        if (!ctype_digit((string)$pid)) {
            trigger_error('$pid should be an integer.');
        }
        if (!ctype_digit((string)$lvl)) {
            trigger_error('$lvl should be an integer.');
        }

        $res = [];
        $qh = q($this->getSQL($this->columns, '`t`.`' . $this->pid_column . '`="' . $pid . '"' . ($this->skip_ids ? ' AND `t`.`' . $this->id_column . '` NOT IN ("' . implode('", "', $this->skip_ids) . '")' : NULL)));

        while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
            $res[$q[$this->id_column]] = ($lvl && $repeat && $spacer ? str_repeat($spacer, $lvl * $repeat) : NULL) . ($this->title_column ? $q[$this->title_column] : NULL);
            $res += $this->getAsArray4Options($q[$this->id_column], $repeat, $spacer, $lvl + 1);
        }

        return $res;
    }

    /**
     * Alias of getAsArray4Options()
     * @param int $pid
     * @param int $repeat
     * @param string $spacer
     * @param int $lvl
     * @todo may delete
     * @return array
     */
    public function getAsArray($pid = 0, $repeat = 3, $spacer = '&nbsp;', $lvl = 0)
    {
        return $this->getAsArray4Options($pid, $repeat, $spacer, $lvl);
    }

    /**
     * Get recursive from childe to top parent
     * @param int $id
     * @param array $where
     * @return array
     */
    public function getFlowUp($id, $where = [])
    {
        $q = ['pid' => $id];
        $res = [];

        while ($q['pid']) {
            $res[] = $q = q_assoc_row($this->getSQL($this->columns, '`t`.`id`="' . $q['pid'] . '"' . ($where ? ' AND ' . implode(' AND ', $where) : NULL) . ''));
        }

        return array_reverse($res);
    }

    /**
     * @param int $id
     * @param null $where
     * @return array
     */
    public function getAllChildren($id, $where = NULL)
    {
        $res = [];

        $qh = q(
            $this->getSQL($this->columns, '`t`.`pid`="' . $id . '"' . ($where ? ' AND (' . $where . ')' : NULL))
        );

        while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
            $res[] = $q;
            $res = array_merge($res, $this->getAllChildren($q['id'], $where));
        }

        return $res;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getRoot($id)
    {
        foreach ($this->getFlowUp($id) as $upper_parent) {
            if (!$upper_parent['pid']) return [
                'id' => $upper_parent['id'],
                'title' => $upper_parent['title']
            ];
        }

        return false;
    }
}