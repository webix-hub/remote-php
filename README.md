Webix Remote for PHP
====================

Simple RPC for Browser <-> Server communications


### Server-side initialization

First of all, you need to create a server. For this, use the following code line:

```php
$api = new Webix\Remote\Server();
```
After that you can use the server-side methods. 
You can add new functions with the help of the *setMethod()* method and then call them on the client side.

You need to pass two parameters to setMethod():

- name - (string) the name of a new function
- function - (function/object) the method/methods that will be created under the given name

Have a look at the example:

```php
// the "add" function
$api->setMethod("add", function($a, $b){
    return $a + $b;
});
```
Here we have defined the "add" function that will sum up two values.

Then you can refer to the registered method from the client side.

###Adding a class

Besides setting a function, you can set a class and specify several functions inside of it.
In the example below we set the *DataDao* class and define the *multiply* function inside of it:

```php
class DataDao{
    public function multiply($a, $b){
        return $a * $b;
    }
}
```
The setClass() method will create an instance of the *DataDao* class. It takes two parameters:

- name - (string) the name of a new class object
- class_object - (object) the class object

```php
$api->setClass("data", new DataDao());
```

###Client-side usage

On the client side you need to include the file with the server-side API after the *webix.js* file:

```html
<script src="webix.js"></script>
<script src="api.php"></script>
```

To call a server-side method you need to use the **webix.remote.methodName** call: *webix.remote.add*.

To refer to a method inside of a class, you will need to use **webix.remote.className.methodName** call, like this:
*webix.remote.data.multiply*.

By default, Webix Remote loads data asynchronously.The client side will get a promise of data first, while real data will come later.

```php
var result = webix.remote.data.multiply(2, 4);
result.then((data) => alert(data));  // 8
```
The *result* in the above example is a promise of data.

It is possible to load data in the synchronous way as well:

```php
var sum = webix.remote.add(2, 4); 
alert(sum); // 6
```
###Adding a Static Value

You can add some static value on the server side and then make use of it on the client side.
Static data can be used to store user data and will be useful for processing user sessions.

To set some static data, use the *setData()* method. It takes two parameters:

- name - (string) the name of parameter that will be set as static
- value - (string) the value that will be set for the static parameter

```php
$api->setData("user", "1");
```

###Implementing CSRF-security

You can ensure safe connection with the server, by setting a CSRF-key as the first parameter during the server creation:

```php
//api.php
$key = $_SESSION["csrf-key"];
$api = new Webix\Remote\Server($key);
```

###API Access Levels

It is possible to limit access to API according to the user's access level.
The user will be able to use this or that method depending on his/her predefined role.

To limit access to a method, you need to specify the acceptable user role during the method's creation like this:

```php
$api->setMethod("role@method_name", function(){
   // some code
});
```

For example, you can limit access to the "add" method by the "admin" role.

```php
$api->setMethod("admin@add", function($a, $b){
    return $a + $b;
});
```

The access levels are defined by the second parameter of the constructor used for server creation:

```php
$api = new Webix\Remote\Server($key, $user);
```

- all methods for which the access modifier isn't set are allowed by default
- if any access modifier is set, methods for which this modifier is set are allowed
- if any access modifier with a particular role is set, methods for which the modifier of this role is set are allowed

The following rule:

```php
$user = ["role" => "admin,levelB"];
```
implies that the add function will be allowed for users with the "user", "user.role=admin" and "user.role=levelB" access modifiers. For a user with a different role the method will be unavailable:

```php
$api->setMethod("user@add1", (a,b) => a+b ); //allowed
$api->setMethod("admin@add2", (a,b) => a+b ); //allowed
$api->setMethod("levelC@add3", (a,b) => a+b ); //blocked
```
