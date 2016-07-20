<?php

require("../sources/Client.php");

//http://some.com/remote/tests/server.php
$url = "http://".$_SERVER["SERVER_ADDR"].str_replace("client_php.php", "server.php", $_SERVER["REQUEST_URI"]);
$sum = $mul = $user = $exc = 0;

try {

	$api = new Webix\Remote\Client($url);

	$sum  = $api->call("add", 3, 2);
	$mul =  $api->call("data.mul", 3, 2);
	$user = $api->get("user");

} catch(Exception $e){
	echo "<pre>";
	echo $e;
}

try {
	$error = $api->call("error");
} catch(Exception $e){	
	$exc = $e->getMessage();
}

check("sum", $sum, 5);
check("mul", $mul, 6);
check("user", $user, 1);
check("err", $exc, "Dummy");


function check($str, $a, $b){
	if ($a != $b)
		echo "<h3 style='color:red'>$str = $a ($b)</h3>";
	else
		echo "<p>$str = $a</p>";
}

