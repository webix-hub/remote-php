<?php
namespace Webix\Remote;

require(__DIR__."/XssFilter.php");

class Server{
	public $parser;
	public $exposeFullAPI = true;
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

	private function decodeName($name, $obj, $access){
		$parts = explode("@", $name);
		if (sizeof($parts) == 1)
			$parts = [["all"], $name];
		else
			$parts[0] = explode(",", $parts[0]);
		
		$parts[] = $obj === false ? $parts[1] : $obj;
		$parts[] = $access;

		return  $parts;		
	}

	function setData($name, $data){
		$this->data[$name] = $data;
	}

	function setClass($name, $obj = false, $access = false){
		$code = $this->decodeName($name, $obj, $access);
		$this->classes[$code[1]] = $code;
	}

	function setMethod($name, $obj = false){
		$code = $this->decodeName($name, $obj, false);
		$this->methods[$code[1]] = $code;
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
					$result = call_user_func_array(array($class[2], $parts[1]), $params);
				else 
					throw new \Exception("Access denied");
			} else {
				$this->filter->filterAll($params);
				$code = $this->methods[$name];
				if ($this->isAllowedMethod($code, false))
					$result = call_user_func_array($code[2], $params);
			}
		} catch(\Exception $e){
			return [ "__webix_remote_error" => $e->getMessage() ];
		}

		return $result;
	}

	protected function checkCSRF($key){
		if ($this->session && $key != $this->session)
			throw new \Exception("Invalid CSRF token");
	}

	protected function isAllowedMethod($obj, $method){
		$access = $obj[0];
		$facade = $obj[3];
		if ($facade && $method){
			$access = isset($facade[$method]) ? $facade[$method] : (isset($facade["*"]) ? $facade["*"] : false);
			if ($access === false || $access === true) return $access;
			if (is_string($access))
				$access = explode(",", $access);
		}


		if (in_array("all", $access)) return true;
		if ($this->user){
			if (in_array("user", $access)) return true;
			$role = isset($this->user["role"]) ? $this->user["role"] : "";
			if (in_array($role, $access)) return true;
		}
		return false;
	}

	protected function toJSON(){
		$data = array();
		$data['$key'] = $this->session;
		$data['$vars'] = [];

		foreach($this->methods as $name => $code)
			if ($this->exposeFullAPI || $this->isAllowedMethod($code, false))
				$data[$name] = 1;

		foreach($this->data as $name => $value)
			$data['$vars'][$name] = $value;

		foreach($this->classes as $name => $obj){
			$subdata = array();
			$names = get_class_methods($obj[2]);
			$facade = $obj[3];

			foreach ($names as $mname)
				if ($mname != "__construct"){
					if ($facade){
						if (isset($facade[$mname])){
							if ($facade[$mname] === false) continue;
						} else {
							if (isset($facade["*"]) && $facade["*"] === false ) continue;
						}
					}
					if ($this->exposeFullAPI || $this->isAllowedMethod($obj, $mname))
						$subdata[$mname] = 1;
				}

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