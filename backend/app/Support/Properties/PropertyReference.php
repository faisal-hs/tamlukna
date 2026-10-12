<?php

namespace App\Support\Properties;

/**
 * Public listing references: "TML-" + 8 random Crockford base-32 characters
 * (no I, L, O, U). Non-sequential, so they reveal nothing about listing volume.
 */
final class PropertyReference
{
    public const PREFIX = 'TML-';

    public const PATTERN = '/^TML-[0-9A-HJKMNP-TV-Z]{8}$/';

    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const LENGTH = 8;

    public static function generate(): string
    {
        $reference = self::PREFIX;

        for ($i = 0; $i < self::LENGTH; $i++) {
            $reference .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $reference;
    }

    public static function isValid(string $reference): bool
    {
        return preg_match(self::PATTERN, $reference) === 1;
    }
}
