<?php

namespace App\Exceptions\Api;

final class InvalidListingTransitionException extends ApiException
{
    public function __construct()
    {
        parent::__construct(409, 'invalid_status_transition', 'api.errors.invalid_status_transition');
    }
}
