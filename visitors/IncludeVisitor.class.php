<?php

use PhpParser\Node;
/** 
  * used to traverse the auxiliary class containing the node 
  * @author Exploit 
 */
class IncludeVisitor extends  PhpParser\NodeVisitorAbstract{
	public $strings = array() ;
	public function leaveNode(Node $node){
		array_push($this->strings, NodeUtils::getNodeStringName($node)) ;
	}
}

?>