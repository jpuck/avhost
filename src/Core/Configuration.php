<?php

namespace jpuck\avhost\Core;

use InvalidArgumentException;
use jpuck\avhost\Core\Contracts\Exportable;
use jpuck\avhost\Core\Traits\EncodeFromArray;
use jpuck\avhost\Core\Utils\Signature;

class Configuration implements Exportable
{
    use EncodeFromArray;

    protected $hostname = '';
    protected $documentRoot = '';
    protected $meta;
    protected $options;
    protected $configurationSsl;
    protected $applicator;
    protected $signature;

    public function __construct(string $hostname, string $documentRoot, $options = null)
    {
        if (isset($options['meta'])) {
            $this->setMeta($options['meta']);
            unset($options['meta']);
        }

        $this->setHostname($hostname);
        $this->setDocumentRoot($documentRoot);

        if (isset($options['ssl'])) {
            $this->ssl($options['ssl']);
            unset($options['ssl']);
        }

        if (isset($options)) {
            $this->setOptions($options);
        }

        $this->applicator = new Applicator($this);

        $this->signature = new Signature($this, $options['signature'] ?? []);
    }

    public static function createFromArray(array $attributes)
    {
        $required = [
            'hostname',
            'documentRoot',
        ];

        foreach ($required as $attribute) {
            if (empty($attributes[$attribute])) {
                throw new MissingAttribute("Missing attribute: $attribute");
            }
        }

        $configuration = new static($attributes['hostname'], $attributes['documentRoot']);

        return parent::createFromArray($attributes, $configuration);
    }

    public function getHostname() : string
    {
        return $this->hostname;
    }

    public function setHostname(string $hostname) : Configuration
    {
        if (!ctype_alnum(str_replace(['-','.'], '', $hostname))) {
            throw new BadHostname("Invalid characters in: $hostname");
        }

        $this->hostname = strtolower($hostname);

        return $this;
    }

    public function getDocumentRoot() : string
    {
        return $this->documentRoot;
    }

    public function setDocumentRoot(string $documentRoot) : Configuration
    {
        $this->documentRoot = $this->getRealReadableFilename($documentRoot, true);

        return $this;
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
            return $this->getOptions()->toArray();
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

    public function getOptions() : Options
    {
        return $this->options ?? $this->options = new Options;
    }

    public function setOptions($options) : Configuration
    {
        if ($options instanceof Options) {
            $this->options = $options;
            return $this;
        }

        if (is_array($options)) {
            $this->options = Options::createFromArray($options);
            return $this;
        }

        throw new BadOptionsType('No matching type for Options');
    }

    public function getMeta() : Meta
    {
        return $this->meta ?? $this->meta = new Meta;
    }

    public function setMeta($meta) : Configuration
    {
        if ($meta instanceof Meta) {
            $this->meta = $meta;
            return $this;
        }

        if (is_array($meta)) {
            $this->meta = Meta::createFromArray($meta);
            return $this;
        }

        throw new BadMetaType('No matching type for Meta');
    }

    protected function setBoolean(array &$array, string $name, $value)
    {
        if (!is_bool($value)) {
            throw new NonBoolean("$option option must be boolean.");
        }

        $array[$name] = $value;
    }

    protected function renderWithoutSignature() : string
    {
        return $this->applicator->render();
    }

    public function render() : string
    {
        return $this->renderWithoutSignature()
            . $this->getMeta()
                ->getSignature()
                ->render([
                    'contentHash' => $this->getContentHash(),
                    'configuration' => $this->toBase64(),
                ]);
    }

    public function getContentHash() : string
    {
        return sha1($this->renderWithoutSignature());
    }

    public function toArray() : array
    {
        $configuration = [
            'hostname' => $this->getHostname(),
            'documentRoot' => $this->getDocumentRoot(),
            'meta' => $this->getMeta()->toArray(),
        ];

        $configuration = array_merge($configuration, $this->getOptions()->toArray());

        if (isset($configuration['ssl'])) {
            $configuration['ssl'] = $configuration['ssl']->toArray();
        }

        return $configuration;
    }

    public function getRealReadableFilename(string $filename, bool $isDirectory = false) : string
    {
        if (!$this->getMeta()->getRealpaths()) {
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

class BadMetaType extends InvalidArgumentException {}
class BadOptionsType extends InvalidArgumentException {}
class MissingAttribute extends InvalidArgumentException {}
class BadHostname extends InvalidArgumentException {}
class NonBoolean extends InvalidArgumentException {}
class UnreadableFile extends InvalidArgumentException {}
class NonDirectory extends InvalidArgumentException {}
