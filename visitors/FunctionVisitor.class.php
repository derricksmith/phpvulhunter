<?php
use PhpParser\Node;

class FunctionVisitor extends PhpParser\NodeVisitorAbstract{
	public $posArr ;   //参数列表
	public $block ;  //当前基本块
	public $vars = array();    //返回的数据array()
	public $sinkType ;   //返回的sink类型
	public $sinkContext ;   // 当前sink上下文
	public $fileSummary ;
	public $scan_type;
	
	public function leaveNode(Node $node){
		//处理过程间代码，即调用的方法定义中的源码
	    if(($node->getType() == 'Expr_FuncCall' || 
		    $node->getType() == 'Expr_MethodCall' || 
		    $node->getType() == 'Expr_StaticCall')){
			//获取到方法的名称
			$nodeName = NodeUtils::getNodeFunctionName($node);
			$ret = NodeUtils::isSinkFunction($nodeName,$this->scan_type);
			//进行危险参数的辨别
			if($ret[0] == true){
				//处理系统内置的sink
				//找到了mysql_query
				$cfg = new CFGGenerator() ;
				
				//array(where)找到危险参数的位置
				$args = $ret[1];
				if (is_array($args[0])){
				    $args = $args[0];
				}
				$vars = $this->senstivePostion($node,$this->block,$args) ;  
				$type = TypeUtils::getTypeByFuncName($nodeName) ;
				
				if($vars){
					//返回处理结果，将多个相关变量位置返回
					$this->vars = array_merge($this->vars, $vars);
				}
				
				if($type){
					//返回sink类型
					$this->sinkType = $type ;
				}
			}elseif(array_key_exists($nodeName,$this->sinkContext->getAllSinks())){
			    //处理已经加入sinksContext用户自定义函数
				//处理用户定义的sink
				$type = TypeUtils::getTypeByFuncName($nodeName) ;
				if($type){
					//返回sink类型
					$this->sinkType = $type ;
				}
				
				$context = Context::getInstance() ;
				$funcName = NodeUtils::getNodeFunctionName($node);
			    $funcBody = $context->getClassMethodBody(
			        $funcName,
			        $this->fileSummary->getPath(),
			        $this->fileSummary->getIncludeMap()
			    );
			    
			    if(!$funcBody) return;
			    $cfg = new CFGGenerator();
			    //$this->block->function[$nodeName]
			    $arr = $this->sinkContext->getAllSinks() ;
			    $arr = $arr[$nodeName] ;
			    foreach ($arr as $pos){
			        $argName = NodeUtils::getNodeFuncParams($node);
			        $argName = $argName[$pos] ;
			        $this->vars = $this->sinkMultiBlockTraceback($argName, $this->block,0);			        
			    }
			}else {
                ;
			}
		}
	}
	
	/**
	 * sink多块回溯
	 * @param string $argName
	 * @param BasicBlock $block
	 * @param flowNum 遍历过的flow数量
	 * @return array
	 */
	public function sinkMultiBlockTraceback($argName,$block,$flowsNum=0){
	    $mulitBlockHandlerUtils = new multiBlockHandlerUtils($block);
	    $blockList = $mulitBlockHandlerUtils->getPathArr();

	    $flows = $block->getBlockSummary()->getDataFlowMap();
	    //当前块flows没有遍历完
	    if(count($flows) != $flowsNum)
	        return $this->sinkTracebackBlock($argName, $block, $flowsNum);
	    
	    if($blockList == null || count($blockList) == 0){
            return  array($argName);
	    }
	    if(!is_array($blockList[0])){
	        //如果不是平行结构
	        $flows = $block->getBlockSummary()->getDataFlowMap();
	        if(count($flows) == $flowsNum){
	            $block = $blockList[0];
	            $ret = $this->sinkTracebackBlock($argName, $block, 0);
	            return $ret;
	        }
	        $ret = $this->sinkTracebackBlock($argName, $block, 0);
	        return $ret;
	    }else{
	        //平行结构
	        //当遇到sink函数时回溯碰到平行结构，那么遍历平行结构，将所有危险的相关变量全部记录下来
	        $retarr = array();
	        foreach ($blockList[0] as $block){
	            $ret = array();
	            $ret = $this->sinkTracebackBlock($argName, $block, 0);
	            $retarr = array_merge($retarr, $ret);
	        }
	        return $retarr;
	        
	    }
	}
	
	/**
	 * 获取敏感sink的参数对应的危险参数
	 * 如 :mysql_query($sql)
	 * 返回sql
	 * @param Node $node
	 * @param BasicBlock $block
	 * @return Ambigous <multitype:, multitype:string >
	 */
	public function senstivePostion($node,$block, $args){
	    $ret = array();
	    //得到sink函数的参数位置(1)
	    //$args = array(0) ;  //1  => mysql_query
	    foreach($args as $arg){
	        //args[$arg-1] sinks函数的危险参数位置商量调整
	        if ($arg > 0){
	            if(count($node->args) > 0){
	                $argNameStr = NodeUtils::getNodeStringName($node->args[$arg-1]) ;   //sql
	                $ret = $this->sinkMultiBlockTraceback($argNameStr ,$block,0);  //array(where,id)
	            }
	            
	        }
	    }
	    return $ret ;
	}
	
	/**
	 * sink单块回溯
	 * @param string $argName
	 * @param BasicBlock $block
	 * @param flowNum 遍历过的flow数量
	 * @return array
	 */
	public function sinkTracebackBlock($argName,$block,$flowsNum){
	    $flows = $block->getBlockSummary()->getDataFlowMap();
	    //需要将遍历过的dataflow删除
	    $temp = $flowsNum;
	    while ($temp>0){
	        array_pop($flows);
	        $temp --;
	    }
	    //将块内数据流逆序，从后往前遍历
	    $flows = array_reverse($flows);
	
	    foreach($flows as $flow){
	        $flowsNum ++;
	        //trace back
	        if($flow->getName() == $argName){
	            //处理净化信息
	            if ($flow->getLocation()->getSanitization()){
	                return "safe";
	            }
	            //得到flow->getValue()的变量node
	            //$sql = $a . $b ;  =>  array($a,$b)
	            if($flow->getValue() instanceof ConcatSymbol){
	                $vars = $flow->getValue()->getItems();
	            }else{
	                $vars = array($flow->getValue()) ;
	            }
	            $retarr = array();
	            foreach($vars as $var){
	                $var = NodeUtils::getNodeStringName($var);
	                $ret = $this->sinkMultiBlockTraceback($var,$block,$flowsNum);
	                //变量经过净化，这不需要跟踪该变量
	                if ($ret == "safe"){
	                    $retarr = array_slice($retarr, array_search($var,$retarr));
	                }else{
	                    $retarr = array_merge($ret,$retarr) ;
	                }
	            }
	            return $retarr;
	        }
	        	
	    }
	    if ($argName instanceof Node){
	        $argName = NodeUtils::getNodeStringName($argName);
	        return array($argName);
	    }
	    return $this->sinkMultiBlockTraceback($argName, $block,$flowsNum);
	    
	}
}

?>