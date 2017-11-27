<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Core\Meta;

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

        $this->assertSame($expected, $imported->toArray());
    }
}
