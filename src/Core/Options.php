<?php

namespace jpuck\avhost\Core;

use jpuck\avhost\Core\Contracts\Exportable;
use jpuck\avhost\Core\Traits\EncodeFromArray;

class Options implements Exportable
{
    use EncodeFromArray;

    protected $indexes = false;
    protected $override = 'None';

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

    public function toArray() : array
    {
        return [
            'indexes' => $this->getIndexes(),
            'override' => $this->getOverride(),
        ];
    }

    public static function createFromArray(array $attributes)
    {
        $options = new static;

        foreach ($attributes as $name => $value) {
            $method = "set$name";
            if (method_exists($options, $method)) {
                $options->$method($attributes[$name]);
            }
        }

        return $options;
    }
}
