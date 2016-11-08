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

	protected function configureEssential() : String {
		return "
			ServerName {$this->hostname}
			ServerAdmin webmaster@{$this->hostname}
			DocumentRoot {$this->document_root}

			<Directory {$this->document_root}>
				Options FollowSymLinks
				AllowOverride All
				Require all granted
			</Directory>

			ErrorLog \${APACHE_LOG_DIR}/{$this->hostname}.error.log
			ErrorLogFormat \"%A [%{cu}t] [%-m:%l] %7F: %E: %M% ,\\ referer\\ %{Referer}i\"
			CustomLog \${APACHE_LOG_DIR}/{$this->hostname}.access.log \"%p %h %l %u %t \\\"%r\\\" %>s %O \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\"\"
		";
	}

	protected function configureRequireSSL() : String {
		if(empty($this->ssl['required'])){
			return "";
		}

		return "
			RewriteEngine On
			RewriteCond %{HTTPS} off
			RewriteRule (.*) https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]
		";
	}

	protected function configureHostPlain() : String {
		return
			"<VirtualHost *:80>\n".
			$this->configureRequireSSL().
			$this->configureEssential().
			"</VirtualHost>\n";
	}

	public function __toString(){
		// strip pretty indented tabs seen here, mixed with spaces
		// http://stackoverflow.com/a/17176793/4233593
		return preg_replace('/\t+/', '', "
		");
	}
}
