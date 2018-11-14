<?php

use PhpParser\Node;
/**
 *
 * @author xyw55
 *        
 */
class LineVisitor extends PhpParser\NodeVisitorAbstract
{
    private $nodes = array();
    private $count = 0;
    public function beforeTraverse(array $nodes)
    {
        $this->nodes = $nodes;
    }
    public function enterNode(Node $node)
    {
        $type = $node->getType();
        switch ($type) {
            case "Stmt_Class":
                $this->count= $this->count+2;
                break;
            case "Stmt_Function":
                $this->count= $this->count+1;
                break;
            default:
                ;
                break;
        }
    }

    
    
    
    //-------------------gettetr && setter----------------------------
    public function getNodes()
    {
        return $this->nodes;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setNodes($nodes)
    {
        $this->nodes = $nodes;
    }

    public function setCount($count)
    {
        $this->count = $count;
    }
}
?>