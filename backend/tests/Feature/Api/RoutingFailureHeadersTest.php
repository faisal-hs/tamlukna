<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Failures raised by the router itself (before route middleware runs)
 * must still be localized and carry a request ID.
 */
final class RoutingFailureHeadersTest extends TestCase
{
    public function test_wrong_method_response_is_localized_and_has_request_id(): void
    {
        $this->withHeaders(['Accept-Language' => 'ar'])
            ->putJson('/api/v1/health')
            ->assertStatus(405)
            ->assertJsonPath('code', 'method_not_allowed')
            ->assertJsonPath('message', 'طريقة الطلب غير مسموحة لهذا المسار.')
            ->assertHeader('Content-Language', 'ar')
            ->assertHeader('X-Request-Id');
    }

    public function test_unversioned_api_path_returns_json_404_with_request_id(): void
    {
        $this->get('/api/not-a-version')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found')
            ->assertHeader('X-Request-Id')
            ->assertHeader('Content-Language');
    }
}
