<?php
session_start();

include_once('settings/server.php');
load_all_classes();

Setting::initialise();

function load_all_classes(){
	foreach(glob("{gr-classes}/*.php",GLOB_BRACE) as $file){
		include_once($file);
	}
}

?>