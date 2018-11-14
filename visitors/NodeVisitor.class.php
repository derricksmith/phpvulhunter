<?php
use PhpParser\Node;
/**
 * Get visitors to all AST nodes in the PHP File
 * @author Administrator
 *
 */
class NodeVisitor extends PhpParser\NodeVisitorAbstract{
	private $nodes = array();
	
	public function beforeTraverse(array $nodes){
		$this->nodes = $nodes ;
	}
	
	//getter
	public function getNodes(){
		return $this->nodes ;
	}
	
}
?>