<?php

namespace jpuck\avhost;

use InvalidArgumentException;
use JsonSerializable;

class ConfigurationSsl implements JsonSerializable
{
    protected $configuration;
    protected $required;
    protected $certificate;
    protected $key;
    protected $chain;
    protected static $attributes = [
        'required' => [
            'required',
            'certificate',
            'key',
        ],
        'optional' => [
            'chain',
        ],
    ];

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
        $required = array_merge(['configuration'], static::$attributes['required']);

        foreach ($required as $property) {
            if (!isset($properties[$property])) {
                throw new MissingSslParameter("Missing parameter: $property");
            }
            $validated[$property] = $properties[$property];
        }

        foreach (static::$attributes['optional'] as $property) {
            if (isset($properties[$property])) {
                $validated[$property] = $properties[$property];
            }
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

    public function jsonSerialize()
    {
        foreach (static::$attributes['required'] as $property) {
            $configuration[$property] = $this->$property;
        }

        foreach (static::$attributes['optional'] as $property) {
            if (isset($this->$property)) {
                $configuration[$property] = $this->$property;
            }
        }

        return $configuration;
    }
}

class MissingSslParameter extends InvalidArgumentException {}
