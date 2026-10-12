<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LogicException;
use Tests\TestCase;

final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_actor_subject_changes_and_request_context(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();

        $request = Request::create('/api/v1/example', 'POST', server: [
            'REMOTE_ADDR' => '10.1.2.3',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
        $request->attributes->set('request_id', 'req-12345678');
        $request->setUserResolver(fn () => $actor);

        $log = app(AuditLogger::class)->record('user.example', $subject, ['field' => 'value'], request: $request);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'actor_id' => $actor->id,
            'action' => 'user.example',
            'auditable_type' => 'user',
            'auditable_id' => $subject->id,
            'source' => 'api',
            'ip_address' => '10.1.2.3',
            'user_agent' => 'PHPUnit',
            'request_id' => 'req-12345678',
        ]);
        $this->assertSame(['field' => 'value'], $log->refresh()->changes);
    }

    public function test_audit_logs_cannot_be_modified(): void
    {
        $log = app(AuditLogger::class)->record('system.example');

        $this->expectException(LogicException::class);
        $log->update(['action' => 'tampered']);
    }

    public function test_role_command_writes_an_audit_record(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->artisan('users:assign-role', ['email' => 'staff@example.com', 'role' => 'staff'])->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_assigned',
            'auditable_type' => 'user',
            'auditable_id' => $user->id,
            'actor_id' => null,
            'source' => 'cli',
        ]);
    }

    public function test_audit_rows_are_not_deleted_with_their_actor(): void
    {
        $actor = User::factory()->create();
        $log = app(AuditLogger::class)->record('system.example', actor: $actor);

        $actor->delete();

        $this->assertNull(AuditLog::query()->findOrFail($log->id)->actor_id);
    }
}
