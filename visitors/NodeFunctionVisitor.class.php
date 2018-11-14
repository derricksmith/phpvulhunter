<?php

use PhpParser\Node;

class NodeFunctionVisitor extends PhpParser\NodeVisitorAbstract{
    public $block;
    public $fileSummary;
    public $cfgGen;
    
    public function leaveNode(Node $node){
        //处理过程间代码，即调用的方法定义中的源码
        if(($node->getType() == 'Expr_FuncCall' ||
            $node->getType() == 'Expr_MethodCall' ||
            $node->getType() == 'Expr_StaticCall' ||
            $node->getType() == "Expr_Isset")){
            $this->cfgGen->functionHandler($node, $this->block, $this->fileSummary);
        
		}
	}
}
?>