<?php

namespace jpuck\avhost\Core;

use jpuck\avhost\Core\Contracts\Exportable;
use jpuck\avhost\Core\Traits\EncodeFromArray;

class Options implements Exportable
{
    use EncodeFromArray;

    protected $indexes = false;
    protected $override = 'None';
    protected $forbidden = false;

    public function getIndexes() : bool
    {
        return $this->indexes;
    }

    public function setIndexes(bool $indexes) : Options
    {
        $this->indexes = $indexes;

        return $this;
    }

    public function getOverride() : string
    {
        return $this->override;
    }

    public function setOverride(string $override) : Options
    {
        $this->override = $override;

        return $this;
    }

    public function getForbidden() : bool
    {
        return $this->forbidden;
    }

    public function setForbidden(bool $forbidden) : Options
    {
        $this->forbidden = $forbidden;

        return $this;
    }

    public function toArray() : array
    {
        return [
            'indexes' => $this->getIndexes(),
            'override' => $this->getOverride(),
            'forbidden' => $this->getForbidden(),
        ];
    }
}
