Webix Remote for PHP
====================

Simple RPC for Browser <-> Server communications


### Server side init


```php
<?php
//api.php

$key = $_SESSION["csrf-key"];
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
class DataDao{
	public function mul($a, $b){
		return $a * $b;
	}
}

$api->setClass("data", new DataDao());


$api->end();
```


### Client side usage

```html
<script src="http://cdn.webix.com/edge/webix.js"></script>
<script src="api.php"></script>
<script>
//async by default
var res = webix.remote.data.mul(2, 4);
res.then((data) => alert(data));  //8

//still, can be sync when necessary
var sum = webix.remote.add(2, 4); //6
alert(sum);
</script>
```