<?php

namespace jpuck\avhost\Core;

use jpuck\avhost\Core\Contracts\Exportable;
use jpuck\avhost\Core\Traits\EncodeFromArray;

class Meta implements Exportable
{
    use EncodeFromArray;

    protected $realpaths = true;

    public function getRealpaths() : bool
    {
        return $this->realpaths;
    }

    public function setRealpaths(bool $realpaths) : Meta
    {
        $this->realpaths = $realpaths;

        return $this;
    }

    public function toArray() : array
    {
        return [
            'realpaths' => $this->getRealpaths(),
        ];
    }
}
