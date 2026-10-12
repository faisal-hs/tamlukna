<?php

namespace App\Exceptions\Api;

final class AccountInactiveException extends ApiException
{
    public function __construct()
    {
        parent::__construct(403, 'account_inactive', 'api.errors.account_inactive');
    }
}
