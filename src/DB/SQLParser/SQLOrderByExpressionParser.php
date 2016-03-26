<?php

namespace TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class SQLOrderByExpressionParser
 */
class SQLOrderByExpressionParser extends SQLExpressionParser
{
    private function parse()
    {
        if ($this->parsed) {
            return;
        }

        $patient = explode(',', trim($this->expression));
        foreach ($patient as &$fragment) {
            $fragment = trim($fragment);
            if (preg_match('/ (asc|desc)$/i', $fragment, $res)) {
                $fragment = [preg_replace('/ (asc|desc)$/i', '', $fragment), $res[1]];
            } else {
                $fragment = [$fragment, 'asc'];
            }
        }

        $this->expression = $patient;

        $this->parsed = true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->parse();
        $res = [];

        foreach ($this->expression as &$expr) {
            $res[] = $expr[0] . ' ' . $expr[1];
        }

        return implode(', ', $res);
    }
}