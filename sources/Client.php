<?php
namespace Webix\Remote;

class Client{
	function __construct($url){
		$data = file_get_contents($url);

		$this->url = $url;
		$this->api = json_decode(substr($data, 13, strlen($data)-15), true);
		$this->token = isset($this->api["\$key"]) ? $this->api["\$key"] : "";
	}

	public function call($method, ...$args){
		$pack = [
			"key"  => $this->token,
			"payload" => json_encode([["name"=>$method, "args" => $args]])
		];

		$result = $this->post($this->url, $pack);
		$obj = json_decode($result, true);

		if ($obj && isset($obj["data"])){
			$data = $obj["data"][0];
			if (is_array($data) && isset($data["__webix_remote_error"]))
				throw new \Exception($data["__webix_remote_error"]);
			return $data;
		}
		throw new \Exception("Webix Remote Error");
	}

	public function get($prop){
		if (isset($this->api["\$vars"][$prop]))
			return $this->api["\$vars"][$prop];
		return null;
	}

	private function post($url, $body){
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);
		curl_close ($ch);

		return $server_output;
	}
}
?>