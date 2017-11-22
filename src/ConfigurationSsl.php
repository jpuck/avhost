<?php

namespace jpuck\avhost;

use InvalidArgumentException;

class ConfigurationSsl
{
    protected $configuration;
    protected $required;
    protected $certificate;
    protected $key;
    protected $chain;

    public function __construct(Configuration $configuration, bool $required, string $certificate, string $key, string $chain = null)
    {
        $this->configuration = $configuration;
        $this->required = $required;

        $this->certificate = $configuration->getRealReadableFilename($certificate);
        $this->key = $configuration->getRealReadableFilename($key);

        if (isset($chain)) {
            $this->chain = $configuration->getRealReadableFilename($chain);
        }
    }

    public static function createFromArray(array $properties)
    {
        $required = [
            'configuration',
            'required',
            'certificate',
            'key',
        ];

        foreach ($required as $property) {
            if (empty($properties[$property])) {
                throw new MissingSslParameter("Missing parameter: $property");
            }
            $validated[$property] = $properties[$property];
        }

        if (!empty($properties['chain'])) {
            $validated['chain'] = $properties['chain'];
        }

        return new static(...array_values($validated));
    }

    public function __get(string $property)
    {
        return $this->$property;
    }

    public function __isset(string $property)
    {
        return isset($this->$property);
    }
}

class MissingSslParameter extends InvalidArgumentException {}
