<?php

namespace jpuck\avhost\Core;

class Applicator
{
    protected $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function render()
    {
        $vhostConfig = $this->getPlainHostConfiguration();

        if(!empty($this->configuration->ssl())){
            $vhostConfig .= PHP_EOL.$this->getSslHostConfiguration();
        }

        return $vhostConfig;
    }

    protected function getPlainHostConfiguration() : string
    {
        return
            '<VirtualHost *:80>'.PHP_EOL.
                $this->indent(
                    $this->getRequireSSL().
                    $this->getCommon()
                ).
            '</VirtualHost>'.PHP_EOL;
    }

    protected function getRequireSSL() : string
    {
        if(empty($this->configuration->ssl()->required)){
            return "";
        }

        return PHP_EOL.$this->getTemplate('requireSsl');
    }

    protected function getCommon() : string
    {
        $variables = [
            'hostname' => $this->configuration->getHostname(),
            'documentRoot' => $this->configuration->getDocumentRoot(),
        ];

        return PHP_EOL
            .$this->getTemplate('name', $variables).PHP_EOL.PHP_EOL
            .$this->getTemplate('blockHidden').PHP_EOL
            .$this->getTemplate('redirectToPrimaryHost', $variables).PHP_EOL.PHP_EOL
            .$this->getDirectoryOptions().PHP_EOL.PHP_EOL
            .$this->getTemplate('logging', $variables).PHP_EOL.PHP_EOL
            .$this->getTemplate('common')
            .PHP_EOL;
    }

    protected function getDirectoryOptions() : string
    {
        $config = $this->configuration->options();
        $documentRoot = $this->configuration->getDocumentRoot();

        if(!empty($config['forbidden'])){
            return "<Directory $documentRoot>Require all denied</Directory>";
        }

        $Indexes = $config['indexes'] ? '+Indexes' : '-Indexes';

        $options = [
            "Options $Indexes +FollowSymLinks -MultiViews",
            "AllowOverride {$config['override']}",
            'Require all granted',
        ];

        $optionBlock = PHP_EOL;
        foreach ($options as $option) {
            $optionBlock .= $this->indent($option).PHP_EOL;
        }

        return "<Directory $documentRoot>$optionBlock</Directory>";
    }

    protected function getSslHostConfiguration() : string
    {
        return
            '<IfModule mod_ssl.c>'.PHP_EOL.
                $this->indent($this->getSslHostContent()).
            '</IfModule>'.PHP_EOL;
    }

    protected function getSslHostContent() : string
    {
        return
            '<VirtualHost *:443>'.PHP_EOL.PHP_EOL.
                $this->indent(
                    $this->getHstsHeader().
                    $this->getCommon().
                    $this->getSslCertificateLines().PHP_EOL.
                    $this->getTemplate('sslOptions')
                ).PHP_EOL.
            '</VirtualHost>'.PHP_EOL;
    }

    protected function getHstsHeader() : string
    {
        if(empty($this->configuration->ssl()->required)){
            return "";
        }

        return $this->getTemplate('hsts');
    }

    protected function getSslCertificateLines() : string
    {
        $ssl = $this->configuration->ssl();

        if (!isset($ssl->certificate)) {
            return '';
        }

        $sslCertificateLines = [
            'SSLEngine on',
            "SSLCertificateFile {$ssl->certificate}",
            "SSLCertificateKeyFile {$ssl->key}",
        ];

        if(isset($ssl->chain)){
            $sslCertificateLines []= "SSLCertificateChainFile {$ssl->chain}";
        }

        return implode(PHP_EOL, $sslCertificateLines).PHP_EOL;
    }

    protected function getTemplate(string $name, array $variables = null) : string
    {
        $filename = __DIR__."/templates/$name";

        if (isset($variables)) {
            extract($variables);
            return require "$filename.php";
        }

        if (!is_readable("$filename.conf")) {
            throw new \InvalidArgumentException("$filename.conf is not readable.");
        }

        return file_get_contents("$filename.conf");
    }

    protected function indent(string $text, int $length = 1, string $indent = "    ")
    {
        $indentation = $indent;
        while(--$length){
            $indentation .= $indent;
        }

        $indented = preg_replace('/^/m', $indentation, $text);

        // strip those indented newlines
        return preg_replace("/^$indentation$/m", '', $indented);
    }
}
