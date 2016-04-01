<?php

namespace TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class SQLWhereParser
 */
class SQLWhereParser
{
    private static $stop_words = [
        ['AND', 3], ['OR', 2]
    ];
    private $where_sql, $data;

    /**
     * @param string $where_sql SQL-conditions to parse
     */
    public function __construct($where_sql)
    {
        $sql = trim($where_sql);
        $this->where_sql = $sql;
        $this->data = new WhereTree(NULL);
        $this->analyze($this->data, 0);
    }

    /**
     * Returns conditions as array
     * @return WhereTree
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $expr
     * @param WhereTree $tree
     * @param string $key
     */
    public function setValue($expr, WhereTree $tree = null, $key = null)
    {
        if ($tree === null) {
            $tree = $this->data;
        }
        if ($key === null) {
            $tree->getValue()->setValue($expr);
        } else {
            foreach ($tree->getByKey($key) as $node) {
                /* @var $node SQLExpressionParser */
                $node->setExpression($expr);
            }
        }
    }

    /**
     * @param string $logical
     * @param string $expr
     * @param WhereTree $tree
     * @param string $key
     * @return WhereTree
     */
    private function addLogicExpression($logical, $expr, WhereTree $tree = null, $key = null)
    {
        if ($tree === null) {
            $tree = $this->data;
        }
        $parent = $tree->getParent();
        if ($parent) {
            $childs = $parent->getChilds();

            foreach ($childs as $k => &$node) {
                /* @var $node WhereTree */
                if ($node === $tree) {
                    $childs[$k] = new WhereTree(null, $parent);
                    $childs[$k]->addChild($tree);
                    $childs[$k]->addChild(new WhereTree($logical, $parent));
                    $childs[$k]->addChild(new WhereTree(new SQLExpressionParser($expr), $parent));

                    return $childs[$k];
                }
            }
        } else {
            $this->data = new WhereTree(null, null);
            $tree->setParent($this->data);
            $this->data->addChild($tree);
            $this->data->addChild(new WhereTree($logical, $parent));
            $this->data->addChild(new WhereTree(new SQLExpressionParser($expr), $parent));

            return $this->data;
        }
    }

    /**
     * @param string $expr
     * @param WhereTree $tree
     * @param string $key
     * @return WhereTree
     */
    public function addAnd($expr, WhereTree $tree = null, $key = null)
    {
        return $this->addLogicExpression('AND', $expr, $tree, $key);
    }

    /**
     * @param string $expr
     * @param WhereTree $tree
     * @param string $key
     * @return WhereTree
     */
    public function addOr($expr, WhereTree $tree = null, $key = null)
    {
        return $this->addLogicExpression('OR', $expr, $tree, $key);
    }

    /**
     * @param WhereTree $data
     * @param int $pos
     * @return bool
     */
    private function analyze(WhereTree $data, $pos)
    {
        $in_backtick = $in_quote = $in_dquote = $in_brackets = 0;
        $patient = $this->where_sql;

        $start = $pos;
        $arrayIndex = 0;
        $first_simbol_after_op = 1;
        for ($i = $pos, $so = strlen($patient); $i < $so; ++$i) {
            if ($patient[$i] == '"') {
                $in_dquote = $in_dquote ? 0 : 1;
            }
            if ($in_dquote) {
                continue;
            }

            if ($patient[$i] == '\'') {
                $in_quote = $in_quote ? 0 : 1;
            }
            if ($in_quote) {
                continue;
            }

            if ($patient[$i] == '`') {
                $in_backtick = $in_backtick ? 0 : 1;
            }
            if ($in_backtick) {
                continue;
            }

            if ($patient[$i] === '(') {
                if ($first_simbol_after_op) {
                    $i = $this->analyze($data->addChild(new WhereTree(null, $data)), $i + 1);
                    $start = $i;
                    $arrayIndex++;
                }
                $in_brackets = 1;
                continue;
            }
            $whitespace_count = strspn($patient[$i], " \n\r\t");
            if ($whitespace_count) {
                $i += $whitespace_count - 1;
                continue;
            }

            if ($patient[$i] == ')') {
                if ($pos) {
                    $expr = trim(substr($patient, $start, $i - $start));
                    if ($expr) {
                        $data->addChild(new WhereTree(new SQLExpressionParser($expr), $data));
                    }
                    return $i + 1;
                }
                $in_brackets = 0;
                continue;
            }

            $first_simbol_after_op = 0;

            foreach (self::$stop_words as $k => &$v) {
                if (substr($patient, $i, $v[1]) !== $v[0]) {
                    continue;
                }
                $expr = trim(substr($patient, $start, $i - $start));
                if ($expr) {
                    $data->addChild(new WhereTree(new SQLExpressionParser($expr), $data));
                }
                $data->addChild(new WhereTree($v[0], $data));

                $start = $i + $v[1];
                $i += $v[1] - 1;
                $i += strspn($patient[$i], " \n\r\t", $i);
                $first_simbol_after_op = 1;
                break;
            }
        }
        if ($start < $i) {
            $expr = trim(substr($patient, $start));
            if ($expr) {
                $data->addChild(new WhereTree(new SQLExpressionParser($expr), $data));
            }
        }
        return $in_backtick || $in_quote || $in_dquote || $in_brackets ? false : $i;
    }

    /**
     * Converts object to SQL-expression
     * @param WhereTree $data Internal data, use without it
     * @return string SQL string
     */
    public function toSQL(WhereTree $data = null)
    {
        if ($data === null) {
            $data = $this->data;
        }
        $res = '';
        foreach ($data->getChilds() as $v) {
            /* @var $v WhereTree */
            if ($v->getChilds()) {
                $res .= '(' . $this->toSQL($v) . ')';
            } elseif ($v->getValue() instanceof SQLExpressionParser) {
                $res .= $v->getValue()->toSQL();
            } else {
                $res .= ' ' . $v->getValue() . ' ';
            }
        }
        return $res . '';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toSQL();
    }
}