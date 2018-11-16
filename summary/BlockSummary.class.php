<?php

require 'Constants.class.php';
require 'DataFlow.class.php';
require 'GlobalDefines.class.php';
require 'RegisterGlobal.class.php';
require 'ReturnValue.class.php';


/**
 * Define basic block summary class
 * In the process of basic block generation, basic block simulation must be performed, that is, the extracted key information is stored in the summary corresponding to the basic block.
 * for subsequent contamination analysis and data flow analysis
 * @author exploit
 */
class BlockSummary {
	Private $dataFlowMap = array() ; //Data stream information
	Private $constantsMap = array() ; //constant information
	Private $globalDefinesMap = array() ; //global variable information
	Private $returnValueMap = array() ; //return value information for in-process analysis
	Private $registerGlobalMap = array() ; //Registration information for global variables, such as extract
	Private $isExitBlock = false ; //whether it is the basic block of exit or die
	
	/**
	* Add a dataFlow record to the map
	* @param DataFlow $dataFlow
	*/
	public function addDataFlowItem($dataFlow){
		array_push($this->dataFlowMap, $dataFlow) ;
	}
	
	/**
	* Add a constant record
	* @param Constants $constants
	*/
	public function addConstantItem($constants){
		array_push($this->constantsMap, $constants) ;
	}
	
	/**
	* Add a global definition message
	* @param GlobalDefines $globalDefines
	*/
	public function addGlobalDefineItem($globalDefines){
		array_push($this->globalDefinesMap,$globalDefines) ;
	} 
	
	/**
	* Add a return message
	* @param ReturnValue $returnValue
	*/
	public function addReturnValueItem($returnValue){
		array_push($this->globalDefinesMap,$returnValue) ;
	}
	
	/**
	* Join a globally registered information
	* @param RegisterGlobal $registerGlobal
	*/
	public function addRegisterGlobalItem($registerGlobal){
		array_push($this->registerGlobalMap, $registerGlobal) ;
	}
	
	//--------------------------------getter && setter------------------------------------------
	public function getDataFlowMap() {
		return $this->dataFlowMap;
	}

	public function getConstantsMap() {
		return $this->constantsMap;
	}

	public function getGlobalDefinesMap() {
		return $this->globalDefinesMap;
	}

	public function getReturnValueMap() {
		return $this->returnValueMap;
	}

	public function getRegisterGlobalMap() {
		return $this->registerGlobalMap;
	}

	public function getIsExitBlock() {
		return $this->isExitBlock;
	}

	public function setDataFlowMap($dataFlowMap) {
		$this->dataFlowMap = $dataFlowMap;
	}

	public function setConstantsMap($constantsMap) {
		$this->constantsMap = $constantsMap;
	}

	public function setGlobalDefinesMap($globalDefinesMap) {
		$this->globalDefinesMap = $globalDefinesMap;
	}

	public function setReturnValueMap($returnValueMap) {
		$this->returnValueMap = $returnValueMap;
	}

	public function setRegisterGlobalMap($registerGlobalMap) {
		$this->registerGlobalMap = $registerGlobalMap;
	}


	public function setIsExitBlock($isExitBlock) {
		$this->isExitBlock = $isExitBlock;
	}
	
	
	
}

?>