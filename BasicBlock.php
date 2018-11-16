<?php

require_once CURR_PATH . '/CFGEdge.php';
require CURR_PATH . '/summary/BlockSummary.class.php';
/**
 * Define basic block information
 * @author exploit
 *
 */
class BasicBlock{
	//AST node contained in the basic block, put in the list
	private $containedNodes ;
	private $blockSummary;
	
	public $is_entry = false ;
	public $is_exit = false ;
	public  $loop_var = NULL;
	//The entry edge of the CFG node
	private $inEdges = array() ;
	//The edge of the CFG node
	private $outEdges = array() ;
	
	
	public function __construct(){
		$this->containedNodes = array() ;
		$this->blockSummary =  new BlockSummary() ;
	}
	
	
	/**
	* Given a node, add it to the containedNodes
	* @param unknown $node
	*/
	public function addNode($node){
		if($node){
			array_push($this->containedNodes, $node) ;
		}else{
			return ;
		}
	}
	
	/**
	* Get all AST nodes in the basic block
	*/
	public function getContainedNodes(){
		return $this->containedNodes ;
	}
	
	public function getBlockSummary() {
		return $this->blockSummary;
	}
	
	public function setBlockSummary($blockSummary) {
		$this->blockSummary = $blockSummary;
	}
	
	/**
	* Add an entry edge to a node in the CFG
	* @param unknown $inEdge
	*/
	public function addInEdge($inEdge){
		if($inEdge){
			array_push($this->inEdges, $inEdge) ;
		}else{
			return ;
		}
	}
	
	/**
	* Adding edges for nodes in CFG
	* @param unknown $outEdge
	*/
	public  function addOutEdge($outEdge){
		if($outEdge){
			array_push($this->outEdges, $outEdge) ;
		}else{
			return ;
		}
	}
	
	//--------------------------Getter && Setter---------------------------------------------
	public function getInEdges() {
		return $this->inEdges;
	}

	public function getOutEdges() {
		return $this->outEdges;
	}

	public function setInEdges($inEdges) {
		$this->inEdges = $inEdges;
	}

	public function setOutEdges($outEdges) {
		$this->outEdges = $outEdges;
	}
}




?>