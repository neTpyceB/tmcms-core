<?php

namespace TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class SQLExpressionParser
 * @package TMCms\DB\SQLParser
 */
class SQLExpressionParser
{
    protected $expression;
    protected $parsed = false;
    private $key;

    /**
     * @param string $expression SQL-expression
     */
    public function __construct($expression)
    {
        $this->setExpression($expression);
    }

    /**
     * Set expression to parse
     * @param string $expression SQL-expression
     * @return $this
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
        $this->parsed = false;

        return $this;
    }

    /**
     * @param string $expression
     * @return $this
     */
    public static function getInstance($expression)
    {
        return new self($expression);
    }

    /**
     * Return string SQL
     * @return string
     */
    public function toSQL()
    {
        $this->parse();

        return $this->expression;
    }

    private function parse()
    {
        if ($this->parsed) {
            return;
        }

        $patient = trim($this->expression);
        $data = preg_split('/=|>=|<=|<|>|<>|\!=|LIKE|IN|NOT\s+IN|NOT\s+LIKE/i', $patient);
        if (count($data) !== 2) {
            $this->key = NULL;
            return;
        }
        $this->key = trim(str_replace('`', NULL, $data[0]));

        $this->parsed = true;
    }

    /**
     * Field key
     * @return string
     */
    public function getKey()
    {
        $this->parse();

        return $this->key;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->parse();

        return $this->expression;
    }
}