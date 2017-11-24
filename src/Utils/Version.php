<?php

namespace jpuck\avhost\Utils;

class Version
{
    public function getVersion() : string
    {
        return getenv('AVHOST_VERSION_NUMBER');
    }
}
