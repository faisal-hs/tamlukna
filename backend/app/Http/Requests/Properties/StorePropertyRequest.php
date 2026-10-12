<?php

namespace App\Http\Requests\Properties;

use App\Models\Property;

final class StorePropertyRequest extends PropertyRequest
{
    /**
     * Runs before validation: users without permission get 403, not field errors.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Property::class) === true;
    }

    protected function isPartial(): bool
    {
        return false;
    }
}
