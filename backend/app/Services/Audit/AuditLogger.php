<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Records important administrative and business actions.
 * HTTP callers pass the Request (actor, IP, user agent and request ID are taken
 * from it); CLI and background callers pass null and an explicit source.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $changes = [],
        ?User $actor = null,
        ?Request $request = null,
        ?string $source = null,
    ): AuditLog {
        $requestUser = $request?->user();
        $actor ??= $requestUser instanceof User ? $requestUser : null;

        $userAgent = $request?->userAgent();
        $requestId = $request?->attributes->get('request_id');

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'changes' => $changes === [] ? null : $changes,
            'source' => $source ?? ($request !== null ? 'api' : 'system'),
            'ip_address' => $request?->ip(),
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            'request_id' => is_string($requestId) ? $requestId : null,
        ]);
    }
}
