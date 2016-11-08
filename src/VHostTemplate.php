<?php
namespace jpuck\avhost;

use InvalidArgumentException;

class VHostTemplate {
	protected $document_root;

	public function __construct(String $host, String $document_root){
		$this->host = $host;
		$this->documentRoot($document_root);
	}

	public function documentRoot(String $document_root = null) : String {
		if(isset($document_root)){
			if(is_dir($document_root)){
				$this->document_root = $document_root;
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
