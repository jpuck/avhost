<?php
namespace jpuck\avhost;

use InvalidArgumentException;

class VHostTemplate {
	protected $hostname = '';
	protected $document_root = '';

	public function __construct(String $hostname, String $document_root){
		$this->hostname($hostname);
		$this->documentRoot($document_root);
	}

	public function hostname(String $hostname = null) : String {
		if(isset($hostname)){
			if(!ctype_alnum(str_replace('-', '', $hostname))){
				throw new InvalidArgumentException(
					"Hostname may only contain alphanumeric characters."
				);
			}
			$this->hostname = strtolower($hostname);
		}
		return $this->hostname;
	}

	public function documentRoot(String $document_root = null) : String {
		if(isset($document_root)){
			if(is_dir($document_root)){
				$this->document_root = realpath($document_root);
			} else {
				throw new InvalidArgumentException(
					"$document_root doesn't exist."
				);
			}
		}
		return $this->document_root;
	}

	public function __toString(){
		// strip pretty indented tabs seen here, mixed with spaces
		// http://stackoverflow.com/a/17176793/4233593
		return preg_replace('/\t+/', '', "
		");
	}
}
