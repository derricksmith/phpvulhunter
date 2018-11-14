<?php
class Branch{
	public $condition ;   //jump condition
	public $nodes ;       //included nodes
	
	/**
		* Constructor
		* @param $cond The condition of the jump
		* @param all nodes carried in the $nodes branch
	*/
	public function __construct($cond, $nodes){
		$this->condition = array($cond) ;
		if(is_array($nodes)){
			$this->nodes = $nodes ;
		}else{
			$this->nodes = array($nodes) ;
		}
		
		
		//Add the conditions of the jump to nodes
		if(is_array($this->condition)){
			foreach ($this->condition as $cond){
				array_unshift($this->nodes, $cond) ;
			}
		}else{
			array_unshift($this->nodes, $this->condition) ;
		}
	}
	
}
?>