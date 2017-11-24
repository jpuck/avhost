<?php

namespace jpuck\avhost\Utils\Contracts;

use JsonSerializable;

interface Exportable extends JsonSerializable
{
    public function toArray() : array;

    public function toJson() : string;
}
