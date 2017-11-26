<?php

namespace jpuck\avhost\Core\Contracts;

use JsonSerializable;

interface Exportable extends JsonSerializable
{
    public function toArray() : array;
    public function toJson() : string;
    public function toBase64() : string;

    public static function createFromArray(array $attributes);
    public static function createFromJson(string $attributes);
    public static function createFromBase64(string $attributes);
}
