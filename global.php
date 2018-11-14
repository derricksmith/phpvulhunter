<?php 
define('CURR_PATH',str_replace("\\", "/", dirname(__FILE__))) ;

require_once CURR_PATH . '/vendor/autoload.php' ;

require_once CURR_PATH . '/BasicBlock.php';
require_once CURR_PATH . '/FileSummaryGenerator.php';

require_once CURR_PATH . '/visitors/IncludeVisitor.class.php';
require_once CURR_PATH . '/visitors/LineVisitor.class.php';
require_once CURR_PATH . '/visitors/NodeVisitor.class.php';
require_once CURR_PATH . '/visitors/NodeFunctionVisitor.class.php';
require_once CURR_PATH . '/visitors/FunctionVisitor.class.php';
require_once CURR_PATH . '/visitors/BranchVisitor.class.php';

require_once CURR_PATH . '/utils/AnalyseUtils.class.php';
require_once CURR_PATH . '/utils/FileUtils.class.php';
require_once CURR_PATH . '/utils/SymbolUtils.class.php';
require_once CURR_PATH . '/utils/NodeUtils.class.php';
require_once CURR_PATH . '/utils/TypeUtils.class.php';
require_once CURR_PATH . '/utils/multiBlockHandlerUtils.class.php';
require_once CURR_PATH . '/utils/SecureUtils.class.php';
require_once CURR_PATH . '/utils/BIFuncUtils.class.php';
require_once CURR_PATH . '/utils/CommonUtils.class.php';

require_once CURR_PATH . '/symbols/Symbol.class.php' ;
require_once CURR_PATH . '/symbols/ValueSymbol.class.php';
require_once CURR_PATH . '/symbols/VariableSymbol.class.php';
require_once CURR_PATH . '/symbols/MutipleSymbol.class.php';
require_once CURR_PATH . '/symbols/ArrayDimFetchSymbol.class.php';
require_once CURR_PATH . '/symbols/ConcatSymbol.class.php';
require_once CURR_PATH . '/symbols/ConstantSymbol.class.php';
require_once CURR_PATH . '/symbols/SanitizationHandler.class.php';
require_once CURR_PATH . '/symbols/EncodingHandler.class.php';

require_once CURR_PATH . '/summary/FileSummary.class.php';

require_once CURR_PATH . '/context/ClassFinder.php';
require_once CURR_PATH . '/context/UserDefinedSinkContext.class.php';
require_once CURR_PATH . '/context/UserSanitizeFuncConetxt.php';
require_once CURR_PATH . '/context/InitModule.class.php';
require_once CURR_PATH . '/context/FileSummaryContext.class.php';
require_once CURR_PATH . '/context/ResultContext.class.php';

require_once CURR_PATH . '/conf/sinks.php' ;
require_once CURR_PATH . '/conf/sources.php' ;
require_once CURR_PATH . '/conf/securing.php';

require_once CURR_PATH . '/analyser/TaintAnalyser.class.php';

require_once CURR_PATH . '/libs/Smarty_setup.php';

require_once CURR_PATH . '/CFGGenerator.php';

header("Content-type:text/html;charset=utf-8") ;

//设置递归层数
ini_set('xdebug.max_nesting_level', 1000);
//设置最大执行时间
ini_set("max_execution_time", "0");
//设置内存限制大小
ini_set('memory_limit', '1000M') ;

$RETURN_STATEMENT = array('Stmt_Return') ;
$STOP_STATEMENT = array('Stmt_Throw','Stmt_Break','Stmt_Continue') ;
$LOOP_STATEMENT = array('Stmt_For','Stmt_While','Stmt_Foreach','Stmt_Do') ;
$JUMP_STATEMENT = array('Stmt_If','Stmt_Switch','Stmt_TryCatch','Expr_Ternary','Expr_BinaryOp_LogicalOr') ;

?>