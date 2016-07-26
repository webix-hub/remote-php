<?php

require("../sources/Server.php");

class DataDao{
	public function mul($a, $b){
		return $a * $b;
	}
}

$key = "SomePerUserSecret";
$api = new Webix\Remote\Server($key);

//add function
$api->setMethod("add", function($a, $b){
	return $a + $b;
});
$api->setMethod("error", function(){
	throw new \Exception("Dummy");
});

//add static value
$api->setData("user", "1");

//add class
$api->setClass("data", new DataDao());

//access modificator

$api->setMethod("admin@adminAdd", function($a, $b){
	return $a + $b;
});
$api->setMethod("user@userAdd", function($a, $b){
	return $a + $b;
});
$api->setClass("admin@adminDAO", new DataDao());

$api->end();