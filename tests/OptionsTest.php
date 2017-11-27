<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Core\Options;

class OptionsTest extends TestCase
{
    public function test_can_instantiate_object()
    {
        $this->assertInstanceOf(Options::class, new Options);
    }

    public function test_can_import_export()
    {
        $expected = [
            'indexes' => true,
            'override' => 'All',
            'forbidden' => true,
        ];

        $options = Options::createFromArray($expected);

        $exported = $options->toArray();

        $imported = Options::createFromArray($exported);

        $this->assertSame($expected, $imported->toArray());
    }
}
