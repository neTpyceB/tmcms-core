<?php

namespace TMCms\DB\SQLParser;

defined('INC') or exit;

/**
 * Class Tree
 */
class Tree
{
    protected $value;

    protected $parent = null;
    protected $childs = [];

    /**
     * @param string $value
     * @param string $parent
     */
    public function __construct($value, $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }

    /**
     * @param Tree $tree
     */
    public function setParent(Tree $tree)
    {
        $this->parent = $tree;
    }

    /**
     * @param Tree $node
     * @param string $key
     * @return Tree
     */
    public function addChild(Tree $node, $key = null)
    {
        if ($key === null) {
            $node->setParent($this);
            $this->childs[] = $node;
        } else {
            $this->childs[$key] = $node;
        }
        return $node;
    }

    /**
     * @return array
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @param int $index
     * @return Tree
     */
    public function getChild($index)
    {
        return isset($this->childs[$index]) ? $this->childs[$index] : null;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return Tree
     */
    public function getParent()
    {
        return $this->parent;
    }
}