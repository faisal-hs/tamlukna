<?php

namespace App\Exceptions\Api;

final class ListingNotEditableException extends ApiException
{
    public function __construct()
    {
        parent::__construct(409, 'listing_not_editable', 'api.errors.listing_not_editable');
    }
}
