<?php

namespace Tests\Unit\Support;

use App\Support\Phone\SaudiPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SaudiPhoneTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validNumbers(): array
    {
        return [
            'local 05' => ['0512345678', '+966512345678'],
            'without leading zero' => ['512345678', '+966512345678'],
            'international plus' => ['+966512345678', '+966512345678'],
            'international 00' => ['00966512345678', '+966512345678'],
            'country code without plus' => ['966512345678', '+966512345678'],
            'spaces and dashes' => ['+966 51-234 5678', '+966512345678'],
            'arabic-indic digits' => ['٠٥١٢٣٤٥٦٧٨', '+966512345678'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_valid_numbers_are_normalized_to_e164(string $input, string $expected): void
    {
        $this->assertSame($expected, SaudiPhone::normalize($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'landline' => ['0112345678'],
            'too short' => ['05123456'],
            'too long' => ['05123456789'],
            'other country' => ['+971501234567'],
            'letters' => ['05ABCDEFGH'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected(string $input): void
    {
        $this->assertNull(SaudiPhone::normalize($input));
    }
}
