<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Base class for expected business errors that need a specific, stable error code.
 * Rendered by ApiExceptionRenderer; not reported to the error log.
 */
abstract class ApiException extends RuntimeException
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        public readonly string $messageKey,
        public readonly array $headers = [],
    ) {
        parent::__construct($errorCode);
    }
}
