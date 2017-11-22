<?php

namespace jpuck\avhost;

use InvalidArgumentException;

class Configuration
{
    protected $hostname = '';
    protected $documentRoot = '';
    protected $options = [
        'indexes' => false,
        'realpaths' => true,
    ];
    protected $configurationSsl;
    protected $applicator;

    public function __construct(string $host, string $documentRoot, array $options = null)
    {
        if(isset($options)){
            $this->options($options);
        }

        $this->hostname($host);
        $this->documentRoot($documentRoot);

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

    public function ssl(array $properties = null)
    {
        if (isset($properties)) {
            $properties['configuration'] = $this;
            $this->configurationSsl = ConfigurationSsl::createFromArray($properties);
        }

        return $this->configurationSsl;
    }

    public function options(array $options = null) : array
    {
        if (is_null($options)) {
            return $this->getOptions();
        }

        foreach(['indexes', 'forbidden', 'realpaths'] as $option){
            if (isset($options[$option])) {
                $this->setBooleanOption($option, $options[$option]);
            }
        }

        if (isset($options['ssl'])) {
            $this->ssl($options['ssl']);
            unset($options['ssl']);
        }

        return $this->getOptions();
    }

    public function getOptions() : array
    {
        return array_replace($this->options, ['ssl' => $this->ssl()]);
    }

    protected function setBooleanOption(string $name, $value)
    {
        if (!is_bool($value)) {
            throw new NonBoolean("$option option must be boolean.");
        }

        $this->options[$name] = $value;
    }

    public function __toString()
    {
        return (string) $this->applicator;
    }

    public function getRealReadableFilename(string $filename, bool $isDirectory = false) : string
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

class NonBoolean extends InvalidArgumentException {}
