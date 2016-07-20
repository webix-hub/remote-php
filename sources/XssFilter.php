<?php

namespace Webix\Remote;

class XssFilter {
	public function filterAll(&$data){
		foreach($data as $name => $record){
			if (is_array($record))
				XssFilter::filterAll($data[$name]);
			else if (is_string($record))
				$data[$name] = $this->filter($record);
		}
	}
	protected function filter($str){
		return filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	}
}