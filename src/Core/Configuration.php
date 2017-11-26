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
    protected $options = [
        'indexes' => false,
        'override' => 'None',
    ];
    protected $metaOptions = [
        'realpaths' => true,
    ];
    protected $configurationSsl;
    protected $applicator;
    protected $signature;

    public function __construct(string $hostname, string $documentRoot, array $options = null)
    {
        if (!empty($options)) {
            $this->options($options);
        }

        $this->hostname($hostname);
        $this->documentRoot($documentRoot);

        $this->applicator = new Applicator($this);

        $this->signature($options['meta']['signature'] ?? []);
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

    public function signature($signature = null) : Signature
    {
        if (isset($signature) && !$signature instanceof Signature) {
            $signature = array_merge($signature, ['configuration' => $this->toArray()]);
            $this->signature = Signature::createFromArray($signature);
        }

        if (is_null($this->signature)) {
            $this->signature = new Signature($this);
        }

        return $this->signature;
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
            return $this->getMeta();
        }

        if (isset($options['signature'])) {
            $this->signature($options['signature']);
        }

        foreach (['realpaths'] as $option) {
            if (isset($options[$option])) {
                $this->setBoolean($this->metaOptions, $option, $options[$option]);
            }
        }

        return $this->getMeta();
    }

    public function getMeta()
    {
        if ($this->signature() !== null) {
            return array_merge($this->metaOptions, ['signature' => $this->signature()->toArray()]);
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

    protected function renderWithoutSignature() : string
    {
        return (string) $this->applicator;
    }

    public function render() : string
    {
        return $this->renderWithoutSignature();
    }

    public function getContentHash() : string
    {
        return sha1($this->renderWithoutSignature());
    }

    public function __toString()
    {
        return $this->render();
    }

    public function toArray() : array
    {
        $configuration = [
            'hostname' => $this->hostname(),
            'documentRoot' => $this->documentRoot(),
            'signature' => $this->signature->toArrayWithoutConfiguration(),
        ];

        $configuration = array_merge($configuration, $this->getOptions());

        if (isset($configuration['ssl'])) {
            $configuration['ssl'] = $configuration['ssl']->toArray();
        }

        return $configuration;
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
