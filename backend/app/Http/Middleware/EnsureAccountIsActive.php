<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\AccountInactiveException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests from suspended or deactivated accounts, even if they still
 * hold a valid token issued before the status changed.
 */
final class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->status->canAuthenticate()) {
            throw new AccountInactiveException;
        }

        return $next($request);
    }
}
