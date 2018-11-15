<?php

use PhpParser\Node;

class NodeFunctionVisitor extends PhpParser\NodeVisitorAbstract{
    public $block;
    public $fileSummary;
    public $cfgGen;
    
    public function leaveNode(Node $node){
        //Process code between processes, ie the source code in the called method definition
        if(($node->getType() == 'Expr_FuncCall' ||
            $node->getType() == 'Expr_MethodCall' ||
            $node->getType() == 'Expr_StaticCall' ||
            $node->getType() == "Expr_Isset")){
            $this->cfgGen->functionHandler($node, $this->block, $this->fileSummary);
		} else {
			echo $node->getType()."<br />";
		}
	}
}
?>