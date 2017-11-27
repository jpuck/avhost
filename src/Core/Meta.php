<?php

namespace jpuck\avhost\Core;

use jpuck\avhost\Core\Contracts\Exportable;
use jpuck\avhost\Core\Traits\EncodeFromArray;
use jpuck\avhost\Core\Utils\Signature;

class Meta implements Exportable
{
    use EncodeFromArray;

    protected $realpaths = true;
    protected $signature;

    public function getRealpaths() : bool
    {
        return $this->realpaths;
    }

    public function setRealpaths(bool $realpaths) : Meta
    {
        $this->realpaths = $realpaths;

        return $this;
    }

    public function getSignature() : Signature
    {
        return $this->signature ?? $this->signature = new Signature;
    }

    public function setSignature($signature) : Meta
    {
        if (!$signature instanceof Signature) {
            $signature = Signature::createFromArray($signature);
        }

        $this->signature = $signature;

        return $this;
    }

    public function toArray() : array
    {
        return [
            'realpaths' => $this->getRealpaths(),
            'signature' => $this->getSignature()->toArray(),
        ];
    }
}
