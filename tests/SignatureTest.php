<?php

use PHPUnit\Framework\TestCase;
use jpuck\avhost\Utils\Signature;

class SignatureTest extends TestCase
{
    public function test_can_instantiate_object()
    {
        $this->assertInstanceOf(Signature::class, new Signature);
    }
}
