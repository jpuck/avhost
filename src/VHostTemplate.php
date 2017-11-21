<?php

namespace jpuck\avhost;

use InvalidArgumentException;

class VHostTemplate {
	protected $hostname = '';
	protected $documentRoot = '';
	protected $ssl = [];
	protected $options = ['indexes' => false];

	public function __construct(String $host, String $documentRoot, Array $options = null){
		$this->hostname($host);
		$this->documentRoot($documentRoot);

		if(isset($options)){
			$this->setOptions($options);
		}

		if(isset($options['crt']) || isset($options['key'])){
			$this->ssl($options);
		}
	}

	protected function setOptions(Array $options){
		foreach(['indexes','forbidden'] as $option){
			if(isset($options[$option])){
				if(!is_bool($options[$option])){
					throw new InvalidArgumentException(
						"if declared, $option option must be boolean."
					);
				}
				$this->options[$option] = $options[$option];
			}
		}
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

	public function documentRoot(String $documentRoot = null) : String {
		if(isset($documentRoot)){
			if(is_dir($documentRoot)){
				$this->documentRoot = realpath($documentRoot);
			} else {
				throw new InvalidArgumentException(
					"$documentRoot doesn't exist."
				);
			}
		}
		return $this->documentRoot;
	}

	public function ssl(Array $ssl = null) : Array {
		if(isset($ssl)){
			$files = ['crt','key'];
			if(!empty($ssl['chn'])){
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
			$this->ssl['req'] = true;

			if($this->options['forbidden'] ?? false){
				$this->ssl['req'] = false;
			}

			if(isset($ssl['req'])){
				if(!is_bool($ssl['req'])){
					throw new InvalidArgumentException(
						"if declared, SSL required must be boolean."
					);
				}
				$this->ssl['req'] = $ssl['req'];
			}
		}
		return $this->ssl;
	}

	protected function getDirectoryOptions() : String {
		if(!empty($this->options['forbidden'])){
			return "
		        Require all denied";
		}

		if($this->options['indexes']){
			$Indexes = '+Indexes';
		} else {
			$Indexes = '-Indexes';
		}

		return "
		        Options $Indexes +FollowSymLinks -MultiViews
		        AllowOverride All
		        Require all granted";
	}

	protected function configureEssential() : String {
		$escaped_hostname = str_replace('.','\\.',$this->hostname);

		return "
		    ServerName {$this->hostname}
		    ServerAlias www.{$this->hostname}
		    ServerAdmin webmaster@{$this->hostname}
		    DocumentRoot {$this->documentRoot}
		    UseCanonicalName On
		    ServerSignature Off

		    # Block access to all hidden files and directories with the exception of
		    # the visible content from within the `/.well-known/` hidden directory.
		    # NOTE: returns 404 resource not found instead of traditional 403 forbidden
		    RewriteEngine On
		    RewriteCond %{REQUEST_URI} \"!(^|/)\\.well-known/([^./]+./?)+\$\" [NC]
		    RewriteCond %{DOCUMENT_ROOT}%{SCRIPT_FILENAME} -d [OR]
		    RewriteCond %{DOCUMENT_ROOT}%{SCRIPT_FILENAME} -f
		    RewriteRule \"(^|/)\\.\" - [R=404,L]

		    RewriteEngine On
		    RewriteCond %{HTTPS} =on
		    RewriteRule ^ - [env=proto:https]
		    RewriteCond %{HTTPS} !=on
		    RewriteRule ^ - [env=proto:http]

		    # redirect all aliases to primary host
		    RewriteCond %{HTTP_HOST} !^$escaped_hostname\$ [NC]
		    RewriteRule ^ %{ENV:PROTO}://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]

		    <Directory {$this->documentRoot}>".
				$this->getDirectoryOptions()."
		    </Directory>

		    ErrorLog \${APACHE_LOG_DIR}/{$this->hostname}.error.log
		    ErrorLogFormat \"%A [%{cu}t] [%-m:%l] %7F: %E: %M% ,\\ referer\\ %{Referer}i\"
		    CustomLog \${APACHE_LOG_DIR}/{$this->hostname}.access.log \"%p %h %l %u %t \\\"%r\\\" %>s %O \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\"\"
			\n"

			.file_get_contents(__DIR__.'/Templates/common.conf');
	}

	protected function configureRequireSSL() : String {
		if(empty($this->ssl['req'])){
			return "";
		}

		return "
		    RewriteEngine On
		    RewriteCond %{HTTPS} off
		    RewriteRule (.*) https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]
		";
	}

	protected function addHstsHeader() : String {
		if(empty($this->ssl['req'])){
			return "";
		}

		return "
		    <IfModule mod_headers.c>
		        Header set Strict-Transport-Security: max-age=31536000
		    </IfModule>
		";
	}

	protected function configureHostPlain() : String {
		return
			"<VirtualHost *:80>\n".
			$this->configureRequireSSL().
			$this->configureEssential().
			"\n</VirtualHost>\n";
	}

	protected function configureHostSSL() : String {
		if(isset($this->ssl['chn'])){
			$SSLCertificateChainFile = "SSLCertificateChainFile {$this->ssl['chn']}";
		} else {
			$SSLCertificateChainFile = '';
		}

		return
			"<IfModule mod_ssl.c>
			    <VirtualHost *:443>\n".
			        $this->indent($this->addHstsHeader()).
			        $this->indent($this->configureEssential()).

			        "
			        SSLEngine on
			        SSLCertificateFile {$this->ssl['crt']}
			        SSLCertificateKeyFile {$this->ssl['key']}
			        $SSLCertificateChainFile

			        <FilesMatch \"\\.(cgi|shtml|phtml|php)\$\">
			            SSLOptions +StdEnvVars
			        </FilesMatch>
			        <Directory /usr/lib/cgi-bin>
			            SSLOptions +StdEnvVars
			        </Directory>

			        BrowserMatch \"MSIE [2-6]\" \\
			            nokeepalive ssl-unclean-shutdown \\
			            downgrade-1.0 force-response-1.0
			        BrowserMatch \"MSIE [17-9]\" ssl-unclean-shutdown

			    </VirtualHost>
			</IfModule>\n";
	}

	protected function indent(String $text, Int $length = 1, $indent = "    "){
		$indentation = $indent;
		while(--$length){
			$indentation .= $indent;
		}
		return str_replace("\n", "\n$indentation", $text);
	}

	public function __toString(){
		$return = $this->configureHostPlain();
		if(!empty($this->ssl)){
			$return .= PHP_EOL . $this->configureHostSSL();
		}
		// strip pretty indented tabs seen here, mixed with spaces
		// http://stackoverflow.com/a/17176793/4233593
		return preg_replace('/(\t+)|([ \t]+$)/m', '', $return);
	}
}
