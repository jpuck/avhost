<?php

namespace jpuck\avhost\Core\Traits;

use jpuck\avhost\Core\Configuration;

trait Configurable
{
    protected $configuration;

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration($configuration)
    {
        if (!$configuration instanceof Configuration) {
            $configuration = Configuration::createFromArray($configuration);
        }

        $this->configuration = $configuration;

        return $this;
    }
}
