<?php
use PhpParser\Node;
class BranchVisitor extends PhpParser\NodeVisitorAbstract{
	public $branches = array() ;
	/**
	 * 将or表达式的分支分离成分支数组
	 * @param $node  LogicalOr节点
	 * @return $branches 分支数组
	 */
	public function leaveNode(Node $node) {
		if($node instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr){
			if(!($node->left instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr) && 
			    !($node->right instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr)){
			    $left_branch = new Branch("if", $node->left) ;
			    $right_branch = new Branch("else", $node->right) ;
				array_push($this->branches,$left_branch) ;
				array_push($this->branches,$right_branch) ;
			}else{
				if(!($node->left instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr)){
				    $left_branch = new Branch("if", $node->left) ;
					array_push($this->branches,$left_branch) ;
				}elseif(!($node->right instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr)){
				    $right_branch = new Branch("else", $node->right) ;
					array_push($this->branches,$right_branch) ;
				}
			}
		}
	}
	
}
?>