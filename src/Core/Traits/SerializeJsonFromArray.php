<?php

namespace jpuck\avhost\Core\Traits;

trait SerializeJsonFromArray
{
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toJson() : string
    {
        return json_encode($this);
    }
}
