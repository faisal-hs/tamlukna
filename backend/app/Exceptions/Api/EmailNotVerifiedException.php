<?php

namespace App\Exceptions\Api;

final class EmailNotVerifiedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(403, 'email_not_verified', 'api.errors.email_not_verified');
    }
}
