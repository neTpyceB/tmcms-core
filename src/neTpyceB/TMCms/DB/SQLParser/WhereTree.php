<?php

namespace neTpyceB\TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class WhereTree
 */
class WhereTree extends Tree
{
    /**
     * @param string $key
     * @return array
     */
    public function getByKey($key)
    {
        $res = array();
        foreach ($this->childs as $node) {
            /* @var $node WhereTree */
            $node_parser = $node->getValue();
            if ($node_parser instanceof SQLExpressionParser) {
                if ($node_parser->getKey() == $key) {
                    $res[] = $node->getValue();
                }
            }
            $subNodes = $node->getByKey($key);
            if ($subNodes) {
                $res = array_merge($res, $subNodes);
            }
        }
        return $res;
    }
}