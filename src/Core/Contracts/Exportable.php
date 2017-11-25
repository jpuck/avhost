<?php

namespace jpuck\avhost\Core\Contracts;

use JsonSerializable;

interface Exportable extends JsonSerializable
{
    public function toArray() : array;

    public function toJson() : string;

    public function toBase64() : string;
}
