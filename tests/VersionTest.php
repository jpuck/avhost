<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Utils\Version;

class VersionTest extends TestCase
{
    public function test_can_getenv_version()
    {
        $this->assertSame(getenv('AVHOST_VERSION_NUMBER'), (new Version)->getVersion());
    }

    public function test_can_get_unknown_version()
    {
        putenv('AVHOST_VERSION_NUMBER');
        $this->assertSame('unknown', (new Version)->getVersion());
    }
}
