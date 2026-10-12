<?php

namespace App\Http\Requests\Properties;

use App\Models\Property;

final class UpdatePropertyRequest extends PropertyRequest
{
    /**
     * Listings the user cannot even see → 404 (existence not revealed);
     * visible but not editable → 403. Runs before validation.
     */
    public function authorize(): bool
    {
        $property = $this->existing();
        $user = $this->user();

        if ($property === null || $user === null) {
            return false;
        }

        if ($user->cannot('view', $property)) {
            abort(404);
        }

        return $user->can('update', $property);
    }

    protected function isPartial(): bool
    {
        return true;
    }

    protected function existing(): ?Property
    {
        $property = $this->route('property');

        return $property instanceof Property ? $property : null;
    }
}
