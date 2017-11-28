<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Core\Meta;
use jpuck\avhost\Core\Configuration;

class MetaTest extends TestCase
{
    public function test_can_instantiate_object()
    {
        $this->assertInstanceOf(Meta::class, new Meta);
    }

    public function test_can_import_export()
    {
        $expected = [
            'realpaths' => false,
        ];

        $meta = Meta::createFromArray($expected);

        $exported = $meta->toArray();

        $imported = Meta::createFromArray($exported);

        $this->assertArraySubset($expected, $imported->toArray());
    }

    public function test_can_set_configuration_in_signature()
    {
        $expected = new Configuration('example.com', '/tmp');

        $actual = (new Meta)->setConfiguration($expected)->getSignature()->getConfiguration();

        $this->assertSame($expected, $actual);
    }
}
