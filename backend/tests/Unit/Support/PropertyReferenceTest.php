<?php

namespace Tests\Unit\Support;

use App\Support\Properties\PropertyReference;
use PHPUnit\Framework\TestCase;

final class PropertyReferenceTest extends TestCase
{
    public function test_generated_references_match_the_public_format(): void
    {
        for ($i = 0; $i < 500; $i++) {
            $reference = PropertyReference::generate();
            $this->assertTrue(PropertyReference::isValid($reference), "Invalid reference: {$reference}");
        }
    }

    public function test_validation_rejects_malformed_and_ambiguous_references(): void
    {
        $this->assertTrue(PropertyReference::isValid('TML-0123ABCZ'));
        $this->assertFalse(PropertyReference::isValid('TML-0123ABC'));    // too short
        $this->assertFalse(PropertyReference::isValid('TML-0123ABCI'));   // I is excluded
        $this->assertFalse(PropertyReference::isValid('tml-0123abcz'));   // lowercase
        $this->assertFalse(PropertyReference::isValid('XYZ-0123ABCZ'));   // wrong prefix
    }
}
