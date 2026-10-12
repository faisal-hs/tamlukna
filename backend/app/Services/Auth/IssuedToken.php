<?php

namespace App\Services\Auth;

use App\Models\User;
use Carbon\CarbonInterface;

final readonly class IssuedToken
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
        public ?CarbonInterface $expiresAt,
    ) {}
}
