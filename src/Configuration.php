<?php

namespace jpuck\avhost;

use InvalidArgumentException;
use jpuck\avhost\Utils\Contracts\Exportable;
use jpuck\avhost\Utils\Traits\SerializeJsonFromArray;

class Configuration implements Exportable
{
    use SerializeJsonFromArray;

    protected $hostname = '';
    protected $documentRoot = '';
    protected $options = [
        'indexes' => false,
        'override' => 'None',
    ];
    protected $metaOptions = [
        'realpaths' => true,
    ];
    protected $configurationSsl;
    protected $applicator;

    public function __construct(string $hostname, string $documentRoot, array $options = null)
    {
        if (!empty($options)) {
            $this->options($options);
        }

        $this->hostname($hostname);
        $this->documentRoot($documentRoot);

        $this->applicator = new Applicator($this);
    }

    public static function createFromArray(array $configuration)
    {
        $required = [
            'hostname',
            'documentRoot',
        ];

        foreach ($required as $attribute) {
            if (empty($configuration[$attribute])) {
                throw new MissingAttribute("Missing attribute: $attribute");
            }

            $$attribute = $configuration[$attribute];

            unset($configuration[$attribute]);
        }

        return new static($hostname, $documentRoot, $configuration);
    }

    public function hostname(string $hostname = null) : string
    {
        if (is_null($hostname)) {
            return $this->hostname;
        }

        if (!ctype_alnum(str_replace(['-','.'], '', $hostname))) {
            throw new BadHostname("Invalid characters in: $hostname");
        }

        return $this->hostname = strtolower($hostname);
    }

    public function documentRoot(string $documentRoot = null) : string
    {
        if (isset($documentRoot)) {
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

        if (isset($options['meta'])) {
            $this->meta($options['meta']);
        }

        // boolean
        foreach (['indexes', 'forbidden'] as $option) {
            if (isset($options[$option])) {
                $this->setBoolean($this->options, $option, $options[$option]);
            }
        }

        // verbatim
        foreach (['override'] as $option) {
            if (isset($options[$option])) {
                $this->options[$option] = $options[$option];
            }
        }

        if (isset($options['ssl'])) {
            $this->ssl($options['ssl']);
        }

        return $this->getOptions();
    }

    public function getOptions() : array
    {
        return array_replace($this->options, [
            'ssl' => $this->ssl(),
            'meta' => $this->meta(),
        ]);
    }

    public function meta(array $options = null)
    {
        if (is_null($options)) {
            return $this->metaOptions;
        }

        foreach (['realpaths'] as $option) {
            if (isset($options[$option])) {
                $this->setBoolean($this->metaOptions, $option, $options[$option]);
            }
        }

        return $this->metaOptions;
    }

    protected function setBoolean(array &$array, string $name, $value)
    {
        if (!is_bool($value)) {
            throw new NonBoolean("$option option must be boolean.");
        }

        $array[$name] = $value;
    }

    public function __toString()
    {
        return (string) $this->applicator;
    }

    public function toArray() : array
    {
        $configuration = [
            'hostname' => $this->hostname(),
            'documentRoot' => $this->documentRoot(),
        ];

        return array_merge($configuration, $this->getOptions());
    }

    public function getRealReadableFilename(string $filename, bool $isDirectory = false) : string
    {
        if (!$this->meta()['realpaths']) {
            return $filename;
        }

        $realpath = realpath($filename);

        if (empty($realpath)) {
            throw new UnreadableFile("$filename is not readable.");
        }

        if ($isDirectory && (!is_dir($realpath))) {
            throw new NonDirectory("$filename is not a directory.");
        }

        return $realpath;
    }
}

class MissingAttribute extends InvalidArgumentException {}
class BadHostname extends InvalidArgumentException {}
class NonBoolean extends InvalidArgumentException {}
class UnreadableFile extends InvalidArgumentException {}
class NonDirectory extends InvalidArgumentException {}
