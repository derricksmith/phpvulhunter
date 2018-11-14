<?php

/**
  * Generate symbols for static strings and integers and floats
  * Notice: The Symbol base class is not inherited because the static string and the value have no associated triplet information.
  * @author exploit
  *
  */
class ValueSymbol {
	
	private $value ; //Value对应的值
	
	/**
	 * 通过AST node来设置Value符号的值
	 * @param AST $node
	 */
	public function setValueByNode($node){
		$type = $node->getType() ;
		if (!property_exists($node,"value")){
			$names = $node->getSubNodeNames();
            foreach ($names as $name){
                $this->value = $node->$name;
            }
		}
		
		$this->value = $node->value ;
	}
	
	/**
	 * @return the $value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param field_type $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	
}

?>