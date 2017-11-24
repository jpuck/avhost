<?php

namespace jpuck\avhost\Core\Utils;

class Version
{
    protected $version = 'unknown';

    public function getVersion() : string
    {
        if (!empty(getenv('AVHOST_VERSION_NUMBER'))) {
            return getenv('AVHOST_VERSION_NUMBER');
        }

        return $this->version;
    }
}
