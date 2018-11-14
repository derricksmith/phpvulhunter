<?php

require_once CURR_PATH . '/vendor/autoload.php';
ini_set('xdebug.max_nesting_level', 2000);

use PhpParser\Node;

/**
 * File processing class
 *
 * @author xyw55
 *
 */
class FileUtils{
   /**
     *
     * @param php project folder path $dirpath
     * @return array of phpfiles
     */
    public static function getPHPfile($dirpath){
        $ret = array();
        if (substr($dirpath, - 4) == ".php"){
            $in_charset = mb_detect_encoding($dirpath) ;
            $dirpath = iconv($in_charset, "UTF-8", $dirpath) ;
            array_push($ret, $dirpath);
        }
        
        if (! is_dir($dirpath)){
            return $ret;
        }
            
        $dh = opendir($dirpath);
        while (($file = readdir($dh)) != false) {
            // The full path of the file name contains the file name
            $filePath = $dirpath . "/" . $file;
            // echo $filePath."<br/>";
            if ($file == "." or $file == ".."){
                continue;
            }elseif (is_dir($filePath)) {
                $files = FileUtils::getPHPfile($filePath);
                foreach ($files as $filePath){
                    if (! is_null($filePath)){
                        $in_charset = mb_detect_encoding($filePath) ;
                        $filePath = iconv($in_charset, "UTF-8", $filePath) ;
                        array_push($ret, $filePath);
                    }
                }
            }elseif (substr($filePath, - 4) == ".php"){
                $in_charset = mb_detect_encoding($filePath) ;
                $filePath = iconv($in_charset, "UTF-8", $filePath) ;
                array_push($ret, $filePath);
            }

        }
        closedir($dh);
        return $ret;
    }

    /**
     * Find main php files by judging the number of Nodes in the file and the number of class nodes and function nodes
     * @param php project folder path $dirpath
     * @return multitype:
     */
    public static function mainFileFinder($dirpath){
        $files = self::getPHPfile($dirpath);
        $should2parser = array();
        $lexer = new PhpParser\Lexer(array(
			'usedAttributes' => array(
				'comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'
			)
		));
		$parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);      
        $traverser = new PhpParser\NodeTraverser();
        $visitor = new VisitorForLine();
        $traverser->addVisitor($visitor);
        foreach ($files as $file) {
            $code = file_get_contents($file);
            try {
                $stmts = $parser->parse($code);
            } catch (PhpParser\Error $e) {
                continue ;
            }
            $traverser->traverse($stmts);
            $nodes = $visitor->getNodes();
            $sumcount = count($nodes);
            $count = $visitor->getCount();
            
            if ($sumcount == 0){
                continue;
            }
            //Temporarily determined when the ratio is less than 0.6, for main php files
            if($count / $sumcount < 0.6){
                array_push($should2parser, $file);
            }
            $visitor->setCount(0);
            $visitor->setNodes(array());
        }
       return $should2parser;
    }
    
    /**
     * require information, the relative path is converted to an absolute path
     * How to handle files with the same name
     * @param string $filePath current file path
     * @param string $rpath
     * @return string
     */
    public static function getAbsPath($filePath, $rpath){
    	global $project_path;
    	global $allFiles;
    	//Complete path
    	$currentDir = dirname($filePath);
    	$absPath = '';
	    if(!strpbrk($rpath, '/')){
	        //require_once "test.php"
	        $absPath = $currentDir . '/' . $rpath;
	    }elseif (substr($rpath, 0, 2) == './'){
	        //require_once "./test.php"
	        $absPath = $currentDir .'/'. substr($rpath, 2);
	    }elseif (substr($rpath, 0, 3) == '../'){
	        //require_once "../test.php" or ../../test.php
	        $tempPath = $currentDir;
	        while(substr($rpath, 0, 3) == '../'){
	            $tempPath = substr($tempPath, 0, strrpos($tempPath, '/'));
	            $rpath = substr($rpath, 3);
	        }
	        $absPath = $tempPath . '/' . $rpath;
	    }
	    //Need to determine whether the file exists, if it does not exist, you need to find in the project
	    if (is_file($absPath)){
	        return $absPath;
	    }else{
	        //require_once CURR_PATH . '/c.php';
	        $pathLen = strlen($rpath);
	        foreach ($allFiles as $fileAbsPath){
	            if(strstr($fileAbsPath, $rpath)){
	                if (is_file($absPath)){
	                   return $fileAbsPath;
	                }
	            }
	        }
	    }
    	return ; 
    }
    

    /**
     * According to the path of the code, the start and end line numbers get the corresponding code
     * @param string $path
     * @param string $startLine
     * @param string $endLine
     */
    public static function getCodeByLine($path, $startLine, $endLine){
        $ret = '' ;
        $startLine = $startLine - 1 ;
        $endLine = $endLine - 1 ;
        $path = str_replace("\\", "/", $path) ;
        if(is_file($path)){
            $codeArr = explode("\n", file_get_contents($path), -1) ;
            $ret = $codeArr[$startLine] ;
            if($startLine != $endLine){
                $ret .= $codeArr[$endLine] ;
            }
            
        }
        
        return $ret ;
    }
    
    /**
      * Recursively get all PHP files under the folder
      * @param unknown $path
      * @return multitype:
      */
    public static function getAllFiles($path){
        static $ret = array() ;
        if(!is_dir($path)){
            array_push($ret, $path) ;
            return $ret ;
        }
        if(($handle = opendir($path)) == false){
            return $ret ;
        }
        while(($file = readdir($handle))!=false){
            if($file == "." || $file == ".."){
                continue ;
            }
            if(is_dir($path . "/" . $file)){
                $item = $path . "/" . $file ;
                $in_charset = mb_detect_encoding($item) ;
                $item = iconv($in_charset, "UTF-8", $item) ;
                array_push($ret, $item) ;
            }else{
                continue ;
            }
        }
        closedir($handle) ;
        return $ret ;
    }

}


?>