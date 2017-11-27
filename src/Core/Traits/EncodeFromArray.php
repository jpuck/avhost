<?php

namespace jpuck\avhost\Core\Traits;

trait EncodeFromArray
{
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toJson() : string
    {
        return json_encode($this);
    }

    public function toBase64() : string
    {
        return base64_encode($this->toJson());
    }

    public static function createFromJson(string $attributes)
    {
        return static::createFromArray(json_decode($attributes, true));
    }

    public static function createFromBase64(string $attributes)
    {
        return static::createFromJson(base64_decode($attributes));
    }

    public static function createFromArray(array $attributes, $object = null)
    {
        if (is_null($object)) {
            $object = new static;
        }

        foreach ($attributes as $name => $value) {
            $method = "set$name";
            if (method_exists($object, $method)) {
                $object->$method($attributes[$name]);
            }
        }

        return $object;
    }
}
