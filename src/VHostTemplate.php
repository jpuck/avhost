<?php
namespace jpuck\avhost;

use InvalidArgumentException;

class VHostTemplate {
	protected $hostname = '';
	protected $document_root = '';
	protected $ssl = [];

	public function __construct(String $host, String $root, Array $ssl = null){
		$this->hostname($host);
		$this->documentRoot($root);
		$this->ssl($ssl);
	}

	public function hostname(String $hostname = null) : String {
		if(isset($hostname)){
			if(!ctype_alnum(str_replace(['-','.'], '', $hostname))){
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

	public function ssl(Array $ssl = null) : Array {
		if(isset($ssl)){
			$files = ['crt','key'];
			if(isset($ssl['chn'])){
				$files[]= 'chn';
			}

			foreach($files as $file){
				if(!isset($ssl[$file])){
					throw new InvalidArgumentException(
						"SSL $file is required."
					);
				}
				if(!file_exists($ssl[$file])){
					throw new InvalidArgumentException(
						"{$ssl[$file]} does not exist."
					);
				}
				$this->ssl[$file] = realpath($ssl[$file]);
			}

			// default required
			if(isset($ssl['required'])){
				if(!is_bool($ssl['required'])){
					throw new InvalidArgumentException(
						"if declared, SSL required must be boolean."
					);
				}
				$this->ssl['required'] = $ssl['required'];
			} else {
				$this->ssl['required'] = true;
			}
		}
		return $this->ssl;
	}

	public function __toString(){
		// strip pretty indented tabs seen here, mixed with spaces
		// http://stackoverflow.com/a/17176793/4233593
		return preg_replace('/\t+/', '', "
		");
	}
}
