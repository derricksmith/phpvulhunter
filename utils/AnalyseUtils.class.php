<?php

/**
 *Analytical tools in analyser
 * @author Exploit
 *
 */
class AnalyseUtils {
	/**
	* Adjust the encoded array
	* @param unknown $encodingArr
	*/
	public static function initEncodeList(&$encodingArr){
		global $F_ENCODING_STRING;
		$len = count($encodingArr) ;
		if($len == 0) return ;
		//Adjustment
		for($i=0;$i<$len;$i++){
			if(in_array($encodingArr[$i], $F_ENCODING_STRING)){
				//Handle url encoding
				switch ($encodingArr[$i]){
					case "rawurlencode":
					case "urlencode":
						//Looking backwards for decoding to see if it can be offset
						for($j=0;$j<$i;$j++){
							if($encodingArr[$i] == "urlencode" && $encodingArr[$j] == "urldecode"){
								array_slice($encodingArr, $i) ;
								array_slice($encodingArr, $j) ;
							}else if($encodingArr[$i] == "rawurlencode" && $encodingArr[$j] == "rawurldecode"){
								array_slice($encodingArr, $i) ;
								array_slice($encodingArr, $j) ;
							}
						}
						break ;
					case "base64_encode":
						//Looking backwards for decoding to see if it can be offset
						for($j=0;$j<$i;$j++){
							if($encodingArr[$j] == "base64_decode"){
								array_slice($encodingArr, $i) ;
								array_slice($encodingArr, $j) ;
							}
						}
						break ;
					case "html_entity_encode":
						//Looking backwards for decoding to see if it can be offset
						for($j=0;$j<$i;$j++){
							if($encodingArr[$j] == "html_entity_encode"){
								array_slice($encodingArr, $i) ;
								array_slice($encodingArr, $j) ;
							}
						}
						break ;
				}
			}
				
				
		}
	}
	
	/**
	* Adjust the cleanup array
	* @param array $saniArr
	*/
	public static function initSaniti(&$saniArr){
		$len = count($saniArr) ;
		if($len == 0) return ;
		for($i=0;$i<$len;$i++){
			//Handle the reverse meaning
			if($saniArr[$i] == "addslashes"){
				//Look backwards for stripslashes to see if it can be offset
				for($j=0;$j<$i;$j++){
					if($saniArr[$j] == "stripcslashes"){
						array_slice($saniArr, $i) ;
						array_slice($saniArr, $j) ;
					}
				}
			}
		}
	}
	
	
	/**
	* Judging the encoding of variables
	* Unsafe if only the variable is decoded
	* If the variable is md5 or sha, it is safe
	* Back:
	* (1)true => encoding security
	* (2)false => encoding is not safe
	* (3)-1 => no coding
	* @param array $encodingArr
	* @return bool
	*/
	public static function check_encoding($encodingArr){
		if(count($encodingArr) == 0){
			return -1 ;
		}
	
		$secu_ins = array('md5','sha1','crc32') ;
		$vul_encode_ins = array('urlencode','base64_encode','html_entity_decode','htmlspecialchars_decode') ;
	
		//If the last encoding is secu_ins, then return false
		$last = array_pop($encodingArr) ;
		if(in_array($last, $secu_ins)){
			return true ;
		}
		
		//If the direct decoding operation, the encoding is not safe
		if(in_array($last, $vul_encode_ins)){
		    return false;
		}
	
	}
}

?>