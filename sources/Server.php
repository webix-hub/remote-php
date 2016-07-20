<?php
namespace Webix\Remote;

require(__DIR__."/XssFilter.php");

class Server{
	public $exposeFullAPI = true;
	public $parser;
	public $filter;

	private $user;
	private $session;
	function __construct($id = false, $user = false){
		$this->methods = array();
		$this->classes = array();
		$this->data = array();

		$this->user = $user;
		$this->session = $id;

		$this->filter = new XssFilter();
	}

	function errorHandler($no, $str){
		header(':', true, 500);
		echo $str;
		die();
	}
	function exceptionHandler($ex){
		header(':', true, 500);
		echo $ex;
		die();
	}

	function addData($name, $data){
		$this->data[$name] = $data;
	}

	function addClass($name, $obj = false){
		if ($obj === false)
			$obj = $name;

		$this->classes[$name] = $obj;
	}

	function addMethod($name, $code = false){
		if ($code === false)
			$code = $name;

		$this->methods[$name] = $code;
	}

	function end(){
		set_error_handler(array($this, "errorHandler"), E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
		set_exception_handler(array($this, "exceptionHandler"));

		if (isset($_POST["payload"])){
			$this->checkCSRF($_POST["key"]);
			$this->outputResult( $this->multicall($_POST["payload"]) );
		} else
			$this->outputAPI();
	}

	protected function multicall($data){
		if (is_string($data))
			$data = json_decode($data, true);

		for ($i=0; $i < sizeof($data) ; $i++)
			$data[$i] = $this->call($data[$i]["name"], $data[$i]["args"]);
	
		return $data;
	}

	protected function call($name, $params){
		if (is_string($params))
			$params = json_decode($params, true);

		try {
			if (strpos($name, ".") !== false){
				//method of class
				$parts = explode(".", $name);
				$class = $this->classes[$parts[0]];
				$this->filter->filterAll($params);
				if ($this->isAllowedMethod($class, $parts[1]))
					$result = call_user_func_array(array($class, $parts[1]), $params);
				else 
					throw new Exception("Access denied");
			} else {
				$this->filter->filterAll($params);
				$code = $this->methods[$name];
				$result = call_user_func_array($code, $params);
			}
		} catch(\Exception $e){
			return [ "__webix_remote_error" => $e->getMessage() ];
		}

		return $result;
	}

	protected function checkCSRF($key){
		if ($this->session && $key != $this->session)
			throw new Exception("Invalid CSRF token");
	}

	protected function isAllowedMethod($obj, $method){
		$access = property_exists($obj, "apiAccess") ? $obj->apiAccess : true;

		if (is_array($access))
			@$access = $access[$method] ? $access[$method] : ($access["*"] ? $access["*"] :false);

		//directly allowed or denied
		if ($access === true || $access === false)
			return $access;

		if (!$this->user)
			return false;

		//allowed for logged in users
		if ($access === "" || $access === "user")
			return true;

		//allowed for specific groups of users
		return $access === $this->user["group"];
	}

	protected function toJSON(){
		$data = array();
		$data['$key'] = $this->session;
		$data['$vars'] = [];

		foreach($this->methods as $name => $code)
			$data[$name] = 1;

		foreach($this->data as $name => $value)
			$data['$vars'][$name] = $value;

		foreach($this->classes as $name => $obj){
			$subdata = array();
			$names = property_exists($obj, "apiMethods") ? $obj->apiMethods : get_class_methods($obj);

			foreach ($names as $mname)
				if ($mname != "__construct")
					if ($this->exposeFullAPI || $this->isAllowedMethod($obj, $mname))
						$subdata[$mname] = 1;

			$data[$name] = $subdata;
		}

		return json_encode($data);
	}
	protected function outputAPI(){
		header("Content-type: application/json");
		echo "webix.remote(".$this->toJSON().");";
	}
	protected function outputResult($value){
		$response = array( "data" => $value );
		header("Content-type: application/json");
		echo json_encode($response);
	}
}
?>