<?php

namespace App\Http\Resources;

/**
 * The advertiser's own listing, including private fields.
 */
final class MyPropertyResource extends PropertyResource
{
    protected bool $includePrivate = true;
}
