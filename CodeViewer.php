<?php

if( !isset($_POST['sinkPath']) || !isset($_POST['argPath'])){
	$data = array("flag"=>false, "msg"=>"文件不存在！");
	$data = json_encode($data);
	echo $data;
	exit() ;
}

$sink_path = $_POST['sinkPath'];
$arg_path = $_POST['argPath'];  


if( $sink_path == $arg_path ){
	$sink_fp = file_get_contents( $sink_path );
	$arg_fp = false;
}
else{
	$sink_fp = file_get_contents( $sink_path ); 
	$arg_fp = file_get_contents( $arg_path ); 
}


$data = array("flag"=>true, "msg_sink"=>$sink_fp, "msg_arg"=>$arg_fp);

$data = '{"flag":true,"msg_sink":"'.$sink_fp.'","msg_arg":"'.$arg_fp.'"}';

echo $data;

?>