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
$api->addMethod("add", function($a, $b){
	return $a + $b;
});
$api->addMethod("error", function(){
	throw new \Exception("Dummy");
});

//add static value
$api->addData("user", "1");

//add class
$api->addClass("data", new DataDao());

$api->end();