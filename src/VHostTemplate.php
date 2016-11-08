<?php
namespace jpuck\avhost;

class VHostTemplate {
	public function __construct(String $host){
		$this->host = $host;
	}

	public function __toString(){
		// strip pretty indented tabs seen here, mixed with spaces
		// http://stackoverflow.com/a/17176793/4233593
		return preg_replace('/\t+/', '', "
		");
	}
}
