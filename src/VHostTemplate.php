<?php

namespace jpuck\avhost;

use InvalidArgumentException;

class VHostTemplate {
	protected $hostname = '';
	protected $documentRoot = '';
	protected $ssl = [];
	protected $options = [
		'indexes' => false,
		'realpaths' => true,
	];

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

	protected function getRealReadableFilename(string $filename, bool $isDirectory = false) : string
	{
		if (!$this->options['realpaths']) {
			return $filename;
		}

		$realpath = realpath($filename);

		if (empty($realpath)) {
			throw new InvalidArgumentException("$filename is not readable.");
		}

		if ($isDirectory && (!is_dir($realpath))) {
			throw new InvalidArgumentException("$filename is not a directory.");
		}

		return $realpath;
	}

	protected function setOptions(Array $options){
		foreach(['indexes', 'forbidden', 'realpaths'] as $option){
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
			$this->documentRoot = $this->getRealReadableFilename($documentRoot, true);
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

				$this->ssl[$file] = $this->getRealReadableFilename($ssl[$file]);
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
			return "<Directory {$this->documentRoot}>Require all denied</Directory>";
		}

		if($this->options['indexes']){
			$Indexes = '+Indexes';
		} else {
			$Indexes = '-Indexes';
		}

		$options = [
			"Options $Indexes +FollowSymLinks -MultiViews",
			'AllowOverride All',
			'Require all granted',
		];

		$optionBlock = PHP_EOL;
		foreach ($options as $option) {
			$optionBlock .= $this->indent($option).PHP_EOL;
		}

		return "<Directory {$this->documentRoot}>$optionBlock</Directory>";
	}

	protected function configureEssential() : String {
		$variables = [
			'hostname' => $this->hostname,
			'documentRoot' => $this->documentRoot,
		];

		return $this->indent(PHP_EOL
			.$this->getConf('name', $variables).PHP_EOL.PHP_EOL
			.$this->getConf('blockHidden').PHP_EOL
			.$this->getConf('redirectToPrimaryHost', $variables).PHP_EOL.PHP_EOL
			.$this->getDirectoryOptions().PHP_EOL.PHP_EOL
			.$this->getConf('logging', $variables).PHP_EOL.PHP_EOL
			.$this->getConf('common')
		);
	}

	protected function getConf(string $name, array $variables = null) : string
	{
		$filename = __DIR__."/Templates/$name";

		if (isset($variables)) {
			extract($variables);
			return require "$filename.php";
		}

		if (!is_readable("$filename.conf")) {
			throw new \InvalidArgumentException("$filename.conf is not readable.");
		}

		return file_get_contents("$filename.conf");
	}

	protected function configureRequireSSL() : String {
		if(empty($this->ssl['req'])){
			return "";
		}

		return $this->getConf('requireSsl');
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
			$this->indent($this->configureRequireSSL()).
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
		return preg_replace('/^/m', $indentation, $text);
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
