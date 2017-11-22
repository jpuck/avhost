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

    public function __construct(string $host, string $documentRoot, array $options = null)
    {
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

    protected function setOptions(array $options)
    {
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

    public function hostname(string $hostname = null) : string
    {
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

    public function documentRoot(string $documentRoot = null) : string
    {
        if(isset($documentRoot)){
            $this->documentRoot = $this->getRealReadableFilename($documentRoot, true);
        }

        return $this->documentRoot;
    }

    public function ssl(array $ssl = null) : array
    {
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

    protected function getDirectoryOptions() : string
    {
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

    protected function configureEssential() : string
    {
        $variables = [
            'hostname' => $this->hostname,
            'documentRoot' => $this->documentRoot,
        ];

        return PHP_EOL
            .$this->getConf('name', $variables).PHP_EOL.PHP_EOL
            .$this->getConf('blockHidden').PHP_EOL
            .$this->getConf('redirectToPrimaryHost', $variables).PHP_EOL.PHP_EOL
            .$this->getDirectoryOptions().PHP_EOL.PHP_EOL
            .$this->getConf('logging', $variables).PHP_EOL.PHP_EOL
            .$this->getConf('common')
            .PHP_EOL;
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

    protected function configureRequireSSL() : string
    {
        if(empty($this->ssl['req'])){
            return "";
        }

        return PHP_EOL.$this->getConf('requireSsl');
    }

    protected function addHstsHeader() : string
    {
        if(empty($this->ssl['req'])){
            return "";
        }

        return $this->getConf('hsts');
    }

    protected function configureHostPlain() : string
    {
        return
            '<VirtualHost *:80>'.PHP_EOL.
                $this->indent(
                    $this->configureRequireSSL().
                    $this->configureEssential()
                ).
            '</VirtualHost>'.PHP_EOL;
    }

    protected function getSslCertificateLines() : string
    {
        if (!isset($this->ssl['crt'])) {
            return '';
        }

        $sslCertificateLines = [
            'SSLEngine on',
            "SSLCertificateFile {$this->ssl['crt']}",
            "SSLCertificateKeyFile {$this->ssl['key']}",
        ];

        if(isset($this->ssl['chn'])){
            $sslCertificateLines []= "SSLCertificateChainFile {$this->ssl['chn']}";
        }

        return implode(PHP_EOL, $sslCertificateLines).PHP_EOL;
    }

    protected function configureHostSSL() : string
    {
        return
            '<IfModule mod_ssl.c>'.PHP_EOL.
                $this->indent($this->getHostSslContent()).
            '</IfModule>'.PHP_EOL;
    }

    protected function getHostSslContent() : string
    {
        return
            '<VirtualHost *:443>'.PHP_EOL.PHP_EOL.
                $this->indent(
                    $this->addHstsHeader().
                    $this->configureEssential().
                    $this->getSslCertificateLines().PHP_EOL.
                    $this->getConf('sslOptions')
                ).PHP_EOL.
            '</VirtualHost>'.PHP_EOL;
    }

    protected function indent(string $text, int $length = 1, string $indent = "    ")
    {
        $indentation = $indent;
        while(--$length){
            $indentation .= $indent;
        }

        $indented = preg_replace('/^/m', $indentation, $text);

        // strip those indented newlines
        return preg_replace('/^    $/m', '', $indented);
    }

    public function __toString()
    {
        $return = $this->configureHostPlain();

        if(!empty($this->ssl)){
            $return .= PHP_EOL . $this->configureHostSSL();
        }

        return $return;
    }
}
