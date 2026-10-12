<?php

namespace App\Exceptions\Api;

final class InvalidVerificationLinkException extends ApiException
{
    public function __construct()
    {
        parent::__construct(403, 'invalid_verification_link', 'api.errors.invalid_verification_link');
    }
}
