<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_health_reports_ok_when_dependencies_are_reachable(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonPath('data.checks.redis', 'ok')
            ->assertJsonStructure(['success', 'message', 'data' => ['status', 'checks', 'time'], 'meta']);
    }

    public function test_health_reports_503_without_leaking_details_when_a_dependency_fails(): void
    {
        Redis::shouldReceive('connection')->andThrow(new RuntimeException('connection refused at 10.0.0.5'));

        $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'service_unavailable')
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonPath('data.checks.redis', 'fail')
            ->assertDontSee('10.0.0.5');
    }
}
