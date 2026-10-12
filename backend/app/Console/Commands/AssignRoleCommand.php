<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Server-side role management (e.g. creating the first admin). Phase 1 exposes
 * no API for changing roles; that is Phase 2 dashboard work.
 */
final class AssignRoleCommand extends Command
{
    protected $signature = 'users:assign-role
        {email : The user\'s email address}
        {role : buyer, renter, owner, broker, developer, staff or admin}
        {--revoke : Remove the role instead of assigning it}';

    protected $description = 'Assign or revoke a platform role (server-side only)';

    public function handle(): int
    {
        $role = Role::tryFrom((string) $this->argument('role'));

        if ($role === null) {
            $this->error('Unknown role. Valid roles: '.implode(', ', array_column(Role::cases(), 'value')));

            return self::FAILURE;
        }

        $user = User::query()->where('email', Str::lower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->error('No user found with that email address.');

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');
        $revoke ? $user->removeRole($role->value) : $user->assignRole($role->value);

        // Persistent audit records arrive with the audit table in Step 7.
        Log::info('Role changed via CLI', [
            'user' => $user->public_id,
            'role' => $role->value,
            'action' => $revoke ? 'revoked' : 'assigned',
        ]);

        $this->info(sprintf('%s role "%s" %s %s.', $revoke ? 'Revoked' : 'Assigned', $role->value, $revoke ? 'from' : 'to', $user->email));

        return self::SUCCESS;
    }
}
