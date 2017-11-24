<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Utils\Version;

class VersionTest extends TestCase
{
    public function test_can_get_version()
    {
        $this->assertSame(getenv('AVHOST_VERSION_NUMBER'), (new Version)->getVersion());
    }
}
