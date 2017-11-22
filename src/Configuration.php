<?php

namespace jpuck\avhost;

use InvalidArgumentException;

class Configuration {
    protected $hostname = '';
    protected $documentRoot = '';
    protected $ssl = [];
    protected $options = [
        'indexes' => false,
        'realpaths' => true,
    ];
    protected $applicator;

    public function __construct(string $host, string $documentRoot, array $options = null)
    {
        if(isset($options)){
            $this->options($options);
        }

        $this->hostname($host);
        $this->documentRoot($documentRoot);

        if(isset($options['crt']) || isset($options['key'])){
            $this->ssl($options);
        }

        $this->applicator = new Applicator($this);
    }

    public function hostname(string $hostname = null) : string
    {
        if (is_null($hostname)) {
            return $this->hostname;
        }

        if(!ctype_alnum(str_replace(['-','.'], '', $hostname))){
            throw new InvalidArgumentException(
                "Hostname may only contain alphanumeric characters."
            );
        }

        return $this->hostname = strtolower($hostname);
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
        if (is_null($ssl)) {
            return $this->ssl;
        }

        $files = ['crt','key'];
        if(!empty($ssl['chn'])){
            $files []= 'chn';
        }

        foreach ($files as $file) {
            if (empty($ssl[$file])) {
                throw new InvalidArgumentException("SSL $file is required.");
            }

            $this->ssl[$file] = $this->getRealReadableFilename($ssl[$file]);
        }

        // default required
        $this->ssl['req'] = true;

        if ($this->options()['forbidden'] ?? false) {
            $this->ssl['req'] = false;
        }

        if (isset($ssl['req'])) {
            if (!is_bool($ssl['req'])) {
                throw new InvalidArgumentException('SSL required is not boolean');
            }
            $this->ssl['req'] = $ssl['req'];
        }

        return $this->ssl;
    }

    public function options(array $options = null) : array
    {
        if (is_null($options)) {
            return $this->options;
        }

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

        return $this->options;
    }

    public function __toString()
    {
        return (string) $this->applicator;
    }

    protected function getRealReadableFilename(string $filename, bool $isDirectory = false) : string
    {
        if (!$this->options()['realpaths']) {
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
}
