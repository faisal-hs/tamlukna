<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use App\Support\Phone\SaudiPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'regex:'.SaudiPhone::PATTERN, Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'preferred_locale' => ['sometimes', 'string', Rule::in(config('tamlukna.locales.supported'))],
            // Only low-privilege roles; never trust the client with anything else.
            'account_type' => ['sometimes', 'string', Rule::in(Role::selfAssignableValues())],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');

        $this->merge(array_filter([
            'email' => is_string($email) ? Str::lower(trim($email)) : null,
            // Invalid numbers are kept as typed so the regex rule reports them.
            'phone' => is_string($phone) && $phone !== '' ? (SaudiPhone::normalize($phone) ?? $phone) : null,
        ], fn ($value) => $value !== null));
    }
}
