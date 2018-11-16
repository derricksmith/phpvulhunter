<?php
require_once 'global.php';
// Define the PHP statement category

use PhpParser\Node ;
class CFGGenerator{
	//AST parsing class
	private $parser ;  
	
	//AST traversal class
	private $traverser ;  
	
	//Global filesummary object
	private $fileSummary ;
	
	//constructor
	public function __construct(){
		$lexer = new PhpParser\Lexer(array(
			'usedAttributes' => array(
				'comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'
			)
		));
		$this->parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);
		$this->traverser = new PhpParser\NodeTraverser ;
		$this->fileSummary = new FileSummary() ;
	}	
	
	//fileSummary get method
	public function getFileSummary() {
	    return $this->fileSummary;
	}
	
	//fileSummary set method
	public function setFileSummary($fileSummary) {
	    $this->fileSummary = $fileSummary;
	}
	
	/**
		* Given a JUMP type Statement, get the branch node
		* @param $node is an AST node (such as If, While, etc.)
	*/
	public function getBranches($node){
		$type = $node->getType();   //Get the statement type of the AST node
		$branches = array() ;   //branch array
		
		switch ($type){
			case 'Stmt_If':
				//Handle the if statement in the if-else structure, including conditions and statements
				$if_branch = new Branch($node->cond, $node->stmts) ;
				array_push($branches,$if_branch) ;
				
				//Processing elseifs, elseifs for the index array, composed of cond and stmts
				$elseifs = $node->elseifs ;
				if($elseifs){
					foreach($elseifs as $if){
						$else_if = new Branch($if->cond, $if->stmts) ;
						array_push($branches,$else_if) ;
					}	
				}
				
				//Processing the else branch, composed of stmts, no cond, here cond filled in "else"
				if($node->else){
					$else_branch = new Branch('else', $node->else->stmts) ;
					array_push($branches,$else_branch) ;
				}
				break ;
				
			case 'Stmt_Switch':
				//The judgment condition in the switch statement
				$cases = $node->cases ;
				foreach($cases as $case){
					//switch+case's condition
					$cond_arr = array($node->cond) ;
					array_push($cond_arr,$case->cond) ;
					
					//Create a branch
					$case_branch = new Branch($cond_arr, $case->stmts) ;
					array_push($branches,$case_branch) ;
				}
				
				break ;
			
			case 'Stmt_TryCatch':
				//try branch
				$try_branch = new Branch(NULL, $node->stmts) ;
				
				//catch branch
				$catches = $node->catches ;
				foreach ($catches as $catch){
					$catch_branch = new Branch($catch->types, $catch->stmts) ;
					array_push($branches, $catch_branch) ;
				}
				break ;
			
			case 'Expr_Ternary':
				//Ternary operation A?B:C
				$if_branch = new Branch($node->cond, $node->if) ;
				array_push($branches, $if_branch) ;
				$else_branch = new Branch('else', $node->else) ;
				array_push($branches,$else_branch) ;
				break ;
			
			case 'Expr_BinaryOp_LogicalOr':
				//A or B logical OR operation
				$visitor = new BranchVisitor() ;
				$this->traverser->addVisitor($visitor) ;
				$this->traverser->traverse(array($node)) ;
				$branches = $visitor->branches ;
				break ;
				
		}
		
		return $branches ;
	}
	
	
	/**
		* Handling loop structure, adding loop variables to basic blocks
		* @param $node AST Node
		* @param $block BasicBlock
	*/
	public function addLoopVariable($node,$block){
		switch ($node->getType()){
			case 'Stmt_For':  //for(i=0;i<3;i++) ===> extract var i
				$block->loop_var = $node->init[0] ;
				break ;
			case 'Stmt_While':  //while(cond) ====> extract cond
				$block->loop_var = $node->cond ;
				break ;
			case 'Stmt_Foreach':  //foreach($nodes as $node) ======> extract $nodes
				$tempNode = clone $node ;
				unset($tempNode->stmts) ;
				$block->loop_var =  $tempNode ;
				break ;
			case 'Stmt_Do':   //do{}while(cond); =====> extract cond
				$block->loop_var = $node->cond ;
				break ;
		}
		// Add the loop condition to $block
		$block->addNode($block->loop_var) ;
		unset($block->loop_var) ;
	}
	
	
	/**
		* Analyze incoming node assignment statements, as well as the current block,
		* Generate a record in the block summary
		* @param ASTNode $node assignment statement
		* @param BasicBlock $block
		* @param string $type handles the var and expr types of the assignment statement (left or right)
	*/
	private function expressionAssignHandler($node,$block,$dataFlow,$type){
	    echo "Calling Function.... CFGGenerator::expressionAssignHandler Type = ".$type."<br />";
		global $scan_type ;
		if($node->getType() == 'Expr_ErrorSuppress'){
			$this->expressionAssignHandler($node->expr,$block,$dataFlow,$type) ;
		}
		
		$part = null ;
		
		if($type == "left"){
			$part = $node->var ;
		}else if($type == "right"){
			$part = $node->expr ;
		}else{
			return ;
		}
		
		
		
		// Handling the assignment of $GLOBALS
		//$GLOBAL['name'] = "chongrui" ; The stream information is $name = "chongrui" ;
		if ($part && SymbolUtils::isArrayDimFetch($part) && 
		      (substr(NodeUtils::getNodeStringName($part),0,7)=="GLOBALS")){
		    //Add dataFlow
		    $arr = new ArrayDimFetchSymbol() ;
		    $arr->setValue($part) ;
		    if($type == "left"){
		        $dataFlow->setLocation($arr) ;
		        $dataFlow->setName(NodeUtils::getNodeGLOBALSNodeName($part)) ;
				echo "Part Name (Global) = ".NodeUtils::getNodeGLOBALSNodeName($part)."<br />";
		        //Add registerglobal
		        $this->registerGLOBALSHandler($part, $block);
		    }else if($type == "right"){
		        $dataFlow->setValue($arr) ;
		    }
		    return ;
		}
		
		
		
		// Processing the assignment statement, stored in the DataFlow
		// Handle the left side of the assignment statement
		if($part && SymbolUtils::isValue($part)){
			//Add Location and name in DataFlow
			$vs = new ValueSymbol() ;
			$vs->setValueByNode($part) ;
			if($type == "left"){
				$dataFlow->setLocation($vs) ;
				$dataFlow->setName($part->name) ;
				echo "Part Name (Value) = ".$part->name."<br />";
			}else if($type == "right"){
				$dataFlow->setValue($vs) ;
			}
		}elseif ($part && SymbolUtils::isVariable($part)){
			//Add dataFlow
			$vars = new VariableSymbol() ;
			$vars->setValue($part);
			echo "Part is Variable, Variable = ".$part->name."<br />";
			if($type == "left"){
				$dataFlow->setLocation($vars) ;
				$dataFlow->setName($part->name) ;
				echo "Part Name (Variable) Left = ".$part->name."<br />";
			}else if($type == "right"){
				$dataFlow->setValue($part) ;
				echo "Part Name (Variable) Right = ".$part->name."<br />";
			}
			
		}elseif ($part && SymbolUtils::isConstant($part)){
			//Add dataFlow
			$con = new ConstantSymbol() ;
			$con->setValueByNode($part) ;
			$con->setName($part->name->parts[0]) ;
			if($type == "left"){
				$dataFlow->setLocation($con) ;
				$dataFlow->setName($part->name) ;
				echo "Part Name (Constant) = ".$part->name."<br />";
			}else if($type == "right"){
				$dataFlow->setValue($con) ;
			}
		}elseif ($part && SymbolUtils::isArrayDimFetch($part)){
			//Add dataFlow
			$arr = new ArrayDimFetchSymbol() ;
			$arr->setValue($part) ;
			$arr->setNameByNode($node);
			if($type == "left"){
				$dataFlow->setLocation($arr) ;
				$dataFlow->setName(NodeUtils::getNodeStringName($part)) ;
				echo "Part Name (ArrayDimFetch) = ".NodeUtils::getNodeStringName($part)."<br />";
			}else if($type == "right"){
				$dataFlow->setValue($arr) ;
			}
		}elseif ($part && SymbolUtils::isConcat($part)){
			$concat = new ConcatSymbol() ;
			$concat->setItemByNode($part) ;
			if($type == "left"){
				$dataFlow->setLocation($concat) ;
				$dataFlow->setName($part->name) ;
				echo "Part Name (Concat) = ".$part->name."<br />";
			}else if($type == "right"){
				$dataFlow->setValue($concat) ;
			}
		}else{
		    //does not belong to any existing symbol type, such as function calls, type conversion
		    echo "Does not belong to any existing symbol type<br />";
			echo "Part type = ".$part->getType()."<br />";
			if($part && ($part->getType() == "Expr_FuncCall" ||
		        $part->getType() == "Expr_MethodCall" ||
		        $part->getType() == "Expr_StaticCall" ) ){

		        //Process id = urlencode($_GET['id']) ;
		        if($type == 'right' && !SymbolUtils::isValue($part)){
					
		            $funcName = NodeUtils::getNodeFunctionName($part) ;
					echo "Function Name = ".$funcName."<br />";
		            BIFuncUtils::assignFuncHandler($part, $type, $dataFlow, $funcName) ;
		            if($dataFlow->getValue() != null){
		                //If the function assignment is processed, it will return immediately
		                $block->getBlockSummary()->addDataFlowItem($dataFlow);
		                return  ;
		            }else{
						//Check if it is a sink function
		                $this->functionHandler($part, $block, $this->fileSummary);
		                 
		                //Processing purification information and coding information
		                SanitizationHandler::setSanitiInfo($part,$dataFlow, $block, $this->fileSummary) ;
		                EncodingHandler::setEncodeInfo($part, $dataFlow, $block, $this->fileSummary) ;
		            }
		        }

		    }
		    //Processing type cast
		    if($part && ($part->getType() == "Expr_Cast_Int" || $part->getType() == "Expr_Cast_Double")
		        && $type == "right"){
		        $dataFlow->getLocation()->setType("int") ;
		        $symbol = SymbolUtils::getSymbolByNode($part->expr) ;
		        $dataFlow->setValue($symbol) ;
		    }
		    //Processing ternary expressions
		    if($part && $part->getType() == "Expr_Ternary"){
		        BIFuncUtils::ternaryHandler($type, $part, $dataFlow) ;
		    }
		    
		    //Handle the variables contained in double quotes
		    if($part && $part->getType() == "Scalar_Encapsed"){
		        $symbol = SymbolUtils::getSymbolByNode($part) ;
		        $dataFlow->setValue($symbol) ;
		    }
			
			
		}//else
		
		//Processed an assignment statement, join the DataFlowMap
		if($type == "right"){
			//echo "<b>Right</b><br />";

			$block->getBlockSummary()->addDataFlowItem($dataFlow);
		} else {
			//echo "<b>Left</b><br />";
		}
		//echo "Data Flow<br />";
		//print_r($dataFlow);
	}
	
	
	/**
		* Handling foreach statement:
		* foreach($_GET['id'] as $key => $value)
		* Convert to two assignments:
		* $key = $_GET
		* $value = $_GET
		* That is, both key and value are infected
		* Saved in the summary of the block
		* @param BasicBlock $block
		* @param Node $node
	*/
	public function foreachHandler($block,$node){
		if($node->expr->getType() == "Expr_ArrayDimFetch"){
			// handle $key
			if($node->keyVar != null){
				$keyFlow = new DataFlow() ;
				$keyFlow->setName(NodeUtils::getNodeStringName($node->keyVar)) ;
				$location = new ArrayDimFetchSymbol() ;
				$location->setValue($node->keyVar) ;
				$keyFlow->setLocation($location) ;
				$keyFlow->setValue($node->expr) ;
				$block->getBlockSummary()->addDataFlowItem($keyFlow) ;
			}
			
			//handle $value
			if($node->valueVar != null){
				$valueFlow = new DataFlow() ;
				$valueFlow->setName(NodeUtils::getNodeStringName($node->valueVar)) ;
				$location = new ArrayDimFetchSymbol() ;
				$location->setValue($node->valueVar) ;
				$valueFlow->setLocation($location) ;
				$valueFlow->setValue($node->expr) ;
				$block->getBlockSummary()->addDataFlowItem($valueFlow) ;
			}
		}
	}
	
	
	/**
		* Process the assigned concat statement, added to the basic block summary
		* @param AST $node
		* @param BasicBlock $block
		* @param string $type
	*/
	private function assignConcatHandler($node,$block,$dataFlow,$type){
		$this->expressionAssignHandler($node, $block,$dataFlow,$type) ;	
	}
	
	
	/**
		* Handling constants, adding constants to the basic block summary
		* @param AST $node
		* @param BasicBlock $block
		* @param string $mode constant mode: define const
	*/
	private function constantHandler($node,$block,$mode){
		if($node == "define"){
			$cons = new Constants() ;
			if (count($node->args) > 1){
			    $cons->setName($node->args[0]->value->value) ;
                $cons->setValue($node->args[1]->value->value) ;
                $block->getBlockSummary()->addConstantItem($cons);
			}		
		}
		
		if($node == "const"){
			$cons = new Constants() ;
			$cons->setName($node->consts[0]->name) ;
			$cons->setValue($node->consts[0]->value) ;
			$block->getBlockSummary()->addConstantItem($cons) ;
		}
	
	}
	
	/**
		* Process the declaration of global variables, add to the summary
		* @param Node $node
		* @param BasicBlock $block
	*/
	private function globalDefinesHandler($node,$block){
		$globalDefine = new GlobalDefines() ;
		if (property_exists($node,"vars")){	
			$names = $node->getSubNodeNames();
            foreach ($names as $name)
                foreach ($node->$name as $parts)
					$globalDefine->setName(NodeUtils::getNodeStringName($parts->name)) ;
					$block->getBlockSummary()->addGlobalDefineItem($globalDefine) ;
		} else {
			$globalDefine->setName(NodeUtils::getNodeStringName($node->value)) ;
			$block->getBlockSummary()->addGlobalDefineItem($globalDefine) ;
		}
	}
	
	/**
		* Extract the return from the basic block
		* @param Node $node
		* @param BasicBlock $block
	*/
	private function returnValueHandler($node,$block){
		$returnValue = new ReturnValue() ;
		$returnValue->setValue($node->expr) ;
		$block->getBlockSummary()->addReturnValueItem($returnValue) ;
	}
	
	/**
		* Get registration information for global variables
		* @param Node $node
		* @param BasicBlock $block
	*/
	private function registerGlobalHandler($node,$block){
		$funcName = NodeUtils::getNodeFunctionName($node);  //Get the method name when the method is called	
		if($funcName != 'extract' and $funcName != "import_request_variables"){
			return ;
		}
		
		switch ($funcName){
			case 'extract':
				$registerItem = new RegisterGlobal() ;
				//extract can only be overridden at URL when EXTR_OVERWRITE
				if(count($node->args) > 1 && $node->args[1]->value->name->parts[0] == "EXTR_OVERWRITE"){
					$registerItem->setIsUrlOverWrite(true) ;
				}else{
					$registerItem->setIsUrlOverWrite(false) ;
				}
				$varName = NodeUtils::getNodeStringName($node->args[0]->value);
				$registerItem->setName($varName) ;
				$block->getBlockSummary()->addRegisterGlobalItem($registerItem) ;
				break ;
				
			case 'import_request_variables':
				$registerItem = new RegisterGlobal() ;
				$varName = NodeUtils::getNodeStringName($node->args[0]->value);
				$registerItem->setName($varName) ;
				$registerItem->setIsUrlOverWrite(true) ;
				$block->getBlockSummary()->addRegisterGlobalItem($registerItem) ;
				break ;	
		}
	}
	
	
	/**
		* Detecting the definition of GLOBALS
		* @param Node $node
		* @param BasicBlock $block
	*/
	private function registerGLOBALSHandler($node,$block){
	    $registerItem = new RegisterGlobal() ;
	    $varName = NodeUtils::getNodeGLOBALSNodeName($node);
	    $registerItem->setName($varName) ;
	    $registerItem->setIsUrlOverWrite(false) ;
	    $block->getBlockSummary()->addRegisterGlobalItem($registerItem) ;
	}
	
	/**
		* Handling user-defined functions
		* @param Node $nodes method calls node
		* @param BasicBlock $block current basic block
		* @return array(position) returns the position of the dangerous parameter
	*/
	private function sinkFunctionHandler($node,$block,$parentBlock){
	    global $scan_type;
		//traverse the code of the function body and get the location of sensitive parameters
		$lexer = new PhpParser\Lexer(array(
			'usedAttributes' => array(
				'comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'
			)
		));
		$parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);
		$traverser = new PhpParser\NodeTraverser;
		$visitor = new FunctionVisitor() ;
		$visitor->fileSummary = $this->fileSummary ;
		$visitor->block = $block ;
		$visitor->scan_type = $scan_type;
		$visitor->sinkContext = UserDefinedSinkContext::getInstance() ;
		$traverser->addVisitor($visitor) ;
		$traverser->traverse(array($node)) ;
		
		//Get the function's parameter name list: array (id, where)
		$del_arg_pos = NodeUtils::getNodeFuncParams($node) ;  
		
		//Method returns the array
		$posArr = array();  
		
		//When the variable cannot be traced or the variable is cleaned, return null
		//$visitor->vars is a list of sensitive parameters
		if((!$visitor->vars) || $visitor->vars == "safe"){
			return null;
		}
		foreach($del_arg_pos as $k => $v){
		    if(in_array($v,$visitor->vars)){
		        //$k+1: The first parameter is recorded as 1 instead of 0.
		        array_push($posArr, ($k+1)) ;
		    }
		}
		//Get the type of sink
		$posArr['type'] = $visitor->sinkType ;
		return $posArr;
	}
	
	
	/**
		* Processing function calls
		* @param node $node the node that called the method
		* @param BasicBlock $block current basic block
		* @param fileSummary $fileSummary current file summary
	*/
	public function functionHandler($node, $block, $fileSummary){  
	    echo "Calling Function.... CFGGenerator::functionHandler<br />";
		//Find the sink function of the phase type according to the scan type specified by the user
	    global $scan_type;
	    //Get the name of the function called to determine whether it is a sink call
	    $funcName = NodeUtils::getNodeFunctionName($node);
		echo "Function Name = ".$funcName."<br />";
	    //Determine whether it is a sink function, the return format is array (true, funcname) or array (false)
	    $ret = NodeUtils::isSinkFunction($funcName, $scan_type);
		//echo "<pre>".$funcName."<br />";
	    if($ret[0] != null && $ret[0] === true){
			//echo "ret[0] === true<br />";
	        //If you find a sink call, start taint analysis
	        $analyser = new TaintAnalyser() ;
	        //Get the location of the dangerous parameters
	        $argPosition = NodeUtils::getVulArgs($node) ;
	        if(count($argPosition) == 0){
	            //echo "argPosition == 0<br />"; 
				return ;
	        }
	        //Get the variable to the location of the dangerous parameter
	        $argArr = NodeUtils::getFuncParamsByPos($node, $argPosition);
	        //Traverse the dangerous parameter name, call the taint analysis function
	        if(count($argArr) > 0){
				//echo "argArr > 0<br />";
				//print_R($argArr);
	            foreach ($argArr as $item){
	                if(is_array($item)){
	                    foreach ($item as $v){
						   echo $v."<br />";
	                       $analyser->analysis($block, $node, $v, $fileSummary) ;
	                    }
	                }else{
						echo $item."<br />";
	                    $analyser->analysis($block, $node, $item, $fileSummary) ;
	                }
	    
	            }
	            	
	        }
	    }else{
	        //If not sink call, start process analysis
	        $context = Context::getInstance() ;
            $funcBody = $context->getClassMethodBody(
            	$funcName,
            	$this->fileSummary->getPath(),
            	$this->fileSummary->getIncludeMap()
	        );

			//check
	        if(!$funcBody || !is_object($funcBody)) return ;
			
	        if($funcBody->getType() == "Stmt_ClassMethod"){
	        	$funcBody->stmts = $funcBody->stmts[0] ;
	        }
            
	        //Build the block and summary of the corresponding method body
	        $nextblock = $this->CFGBuilder($funcBody->stmts, NULL, NULL, NULL) ;
            
	        //ret dangerous parameter location such as: array(0)
	        $ret = $this->sinkFunctionHandler($funcBody, $nextblock, $block);
	        
	        if(!$ret){
	            return ;
	        }
  
	        //Found the array ('del', array (0));
	        $userDefinedSink = UserDefinedSinkContext::getInstance() ;
	        //$type应该从visitor中获取，使用$ret返回
	        $type = $ret['type'] ;
	        unset($ret['type']) ;
	        //Add user sink context
	        $item = array($funcName,$ret) ;
	        $userDefinedSink->addByTagName($item, $type) ;
	        
	        //Start taint analysis
            $argPosition = NodeUtils::getVulArgs($node) ;
            
            $argArr = NodeUtils::getFuncParamsByPos($node, $argPosition);	            
            
	        if(count($argArr) > 0){
	        	$analyser = new TaintAnalyser() ;
	        	foreach ($argArr as $item){
	        		if(is_array($item)){
	        			foreach ($item as $v){
	        				$analyser->analysis($block, $node, $v, $this->fileSummary) ;
	        			}
	        		}else{
	        			$analyser->analysis($block, $node, $item, $this->fileSummary) ;
	        		}
	        	  
	        	}
	        
	        }
	    }
		echo "</pre>";
	}
	
	
	/**
		* Generate basic block summary to prepare for data flow analysis
		* 1, processing assignment statements
		* 2, record global variable definition
		* 3, record global variable registration
		* 4, record the return value
		* 5, record constant definition
		* @param BasicBlock $block
	*/
	public function simulate($block){
		echo "<pre>";
		//Get all the nodes in the basic block
		$nodes = $block->getContainedNodes() ;
		//Loop the nodes collection, collect information into the blocksummary
		foreach ($nodes as $node){
		    if($node->getType() == 'Expr_ErrorSuppress'){
		        $node = $node->expr ;
		    }
			
			if($node instanceof Node\Stmt\Expression){
				$node = $node->expr ;
			}
			
			switch ($node->getType()){
				//Processing assignment statements	
				case 'Expr_Assign':  
					$dataFlow = new DataFlow() ;
					$this->expressionAssignHandler($node, $block,$dataFlow,"left") ;
					$this->expressionAssignHandler($node, $block,$dataFlow,"right") ;
					break ;
				
				//Processing foreach, converted to assignment in the summary
				case 'Stmt_Foreach':	
					$this->foreachHandler($block, $node) ;
					break ;
				
				//Handle string connection assignment
				//$sql .= "from users where" generates sql => "from users where"
				case 'Expr_AssignOp_Concat':		
					$dataFlow = new DataFlow() ;
					$this->assignConcatHandler($node, $block,$dataFlow,"left") ;
					$this->assignConcatHandler($node, $block,$dataFlow,"right") ;
					break ;
				
				// Handle the constant, add to the summary
				// should use define to judge
				case 'Expr_FuncCall' && (NodeUtils::getNodeFunctionName($node) == "define"):
					$this->constantHandler($node, $block,"define") ;
					break ;
				
				//Handling const key definition constants
				case 'Stmt_Const':
					$this->constantHandler($node, $block,"const") ;
					break ;
				
				//Handle the definition of global variables, global $a
				case 'Stmt_Global':
					$this->globalDefinesHandler($node, $block) ;
					break ;
					
				//In-process analysis record
				case 'Stmt_Return':					
					$this->returnValueHandler($node, $block) ;
					break ;
				
				// Global variable registration extract, import_request_variables
				// Identify the purification value
				case 'Expr_FuncCall' && 
				     (NodeUtils::getNodeFunctionName($node) == "import_request_variables" || 
				     NodeUtils::getNodeFunctionName($node) == "extract") :
					$this->registerGlobalHandler($node, $block) ;
					break ;
					
				//If $GLOBALS['name'] = 'xxxx' ; then merge into registerGlobal
				case 'Expr_ArrayDimFetch' && (substr(NodeUtils::getNodeStringName($node),0,7) == "GLOBALS"):
				    $this->registerGLOBALSHandler($node, $block);
				    break;
				    
				// Handle function calls and calls to class methods
				// Interprocess analysis and stain analysis
				case 'Expr_MethodCall':
				case 'Expr_Include':
                case 'Expr_StaticCall':
				case 'Stmt_Echo':
				case 'Expr_Print':
				case 'Expr_FuncCall':
				case 'Expr_Eval':
					$this->functionHandler($node, $block, $this->fileSummary);
					break ;
				default:
				    $traverser = new PhpParser\NodeTraverser;
				    $visitor = new NodeFunctionVisitor() ;
				    $visitor->block = $block;
				    $visitor->fileSummary = $this->fileSummary;
				    $visitor->cfgGen = new CFGGenerator();
				    $traverser->addVisitor($visitor) ;
				    $traverser->traverse(array($node)) ;
				    break;
			}
		}
	}
	
	
	/**
		* Create the corresponding CFG from the AST node for subsequent analysis
		*
		* @param Node $nodes all nodes of the incoming PHP file
		* @param $condition Jump information when building CFGNode
		* @param BasicBlock $pEntryBlock entry basic block
		* @param $pNextBlock next basic block
	*/
	public function CFGBuilder($nodes,$condition,$pEntryBlock,$pNextBlock){
		
		//fileSummary of this file
		global $JUMP_STATEMENT,$LOOP_STATEMENT,$STOP_STATEMENT,$RETURN_STATEMENT ;
		$currBlock = new BasicBlock() ;
		
		//Create a side of a CFG node
		if($pEntryBlock){
			$block_edge = new CFGEdge($pEntryBlock, $currBlock,$condition) ;
			$pEntryBlock->addOutEdge($block_edge) ;
			$currBlock->addInEdge($block_edge) ;
		}
		
		//Handle only one node node, not an array
        if (!is_array($nodes)){
            $nodes = array($nodes);
        }
        
		//Iterate each AST node
		foreach($nodes as $node){
			//Collect the require include_once include_once PHP file name in the node
			$this->fileSummary->addIncludeToMap(NodeUtils::getNodeIncludeInfo($node)) ;
			
			if(!is_object($node)) continue ;
			
			//Do not analyze function definitions
			if($node->getType() == "Stmt_Function"){
				continue ;
			}
			
			//If the node is a jump type statement
			if(in_array($node->getType(), $JUMP_STATEMENT)){
				//Generate a summary of the basic block
				$this->simulate($currBlock) ;
				$nextBlock = new BasicBlock() ;
				//For each branch, establish the corresponding basic block
				$branches = $this->getBranches($node) ;
				foreach ($branches as $b){
					$this->CFGBuilder($b->nodes, $b->condition, $currBlock, $nextBlock)	;				
				}
				$currBlock = $nextBlock ;
				
			//If the node is a loop statement
			}elseif(in_array($node->getType(), $LOOP_STATEMENT)){  
				//Add a loop condition
				$this->addLoopVariable($node, $currBlock) ;
				$this->simulate($currBlock) ;
				//Processing the loop body
				$nextBlock = new BasicBlock() ;
				$this->CFGBuilder($node->stmts, NULL, $currBlock, $nextBlock) ;
				$currBlock = $nextBlock ;
			
			//If the node is the end statement throw break continue
			}elseif(in_array($node->getType(), $STOP_STATEMENT)){
				$currBlock->is_exit = true ;
				break ;
			
			//If the node is return
			}elseif(in_array($node->getType(),$RETURN_STATEMENT)){
				$currBlock->addNode($node) ;
				$this->simulate($currBlock) ;
				return $currBlock ;
			}else{
			    $currBlock->addNode($node);
			}
		}
		
		$this->simulate($currBlock) ;
		
		if($pNextBlock && !$currBlock->is_exit){
			$block_edge = new CFGEdge($currBlock, $pNextBlock) ;
			$currBlock->addOutEdge($block_edge) ;
			$pNextBlock->addInEdge($block_edge) ;
		}
		
		return $currBlock ;
	}
	
}
?>