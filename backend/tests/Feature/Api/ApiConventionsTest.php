<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class ApiConventionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1/__test')->group(function (): void {
            Route::post('validation', fn (Request $request) => ApiResponse::success(
                $request->validate(['name' => ['required', 'string', 'max:10']])
            ));
            Route::get('boom', fn () => throw new RuntimeException('SECRET_DB_PASSWORD=hunter2'));
            Route::get('missing-model', fn () => throw (new ModelNotFoundException)->setModel(User::class, [42]));
            Route::get('forbidden', fn () => throw new AuthorizationException);
            Route::get('unauthenticated', fn () => throw new AuthenticationException);
            Route::get('throttled', fn () => ApiResponse::success())->middleware('throttle:2,1');
            Route::get('paginated', fn () => ApiResponse::paginated(
                new LengthAwarePaginator([['id' => 1], ['id' => 2]], total: 5, perPage: 2, currentPage: 1),
                JsonResource::class,
            ));
        });
    }

    public function test_validation_errors_use_the_standard_envelope(): void
    {
        $this->postJson('/api/v1/__test/validation', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['success', 'message', 'code', 'data', 'errors' => ['name'], 'meta']);
    }

    public function test_messages_follow_accept_language(): void
    {
        $this->withHeaders(['Accept-Language' => 'ar-SA,ar;q=0.9,en;q=0.8'])
            ->postJson('/api/v1/__test/validation', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'البيانات المدخلة غير صالحة.')
            ->assertHeader('Content-Language', 'ar');
    }

    public function test_unsupported_language_falls_back_to_default_locale(): void
    {
        $this->withHeaders(['Accept-Language' => 'fr-FR'])
            ->postJson('/api/v1/__test/validation', [])
            ->assertHeader('Content-Language', config('app.locale'));
    }

    public function test_unknown_api_route_returns_json_404_without_accept_header(): void
    {
        $this->get('/api/v1/definitely-not-a-route')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('code', 'not_found')
            ->assertHeader('X-Request-Id');
    }

    public function test_model_not_found_does_not_leak_model_names(): void
    {
        $this->getJson('/api/v1/__test/missing-model')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found')
            ->assertDontSee('Models');
    }

    public function test_unexpected_errors_are_generic_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $this->getJson('/api/v1/__test/boom')
            ->assertStatus(500)
            ->assertJsonPath('code', 'server_error')
            ->assertJsonPath('message', 'An unexpected error occurred.')
            ->assertJsonMissingPath('meta.debug')
            ->assertDontSee('hunter2');
    }

    public function test_wrong_http_method_returns_405(): void
    {
        $this->putJson('/api/v1/health')
            ->assertStatus(405)
            ->assertJsonPath('code', 'method_not_allowed');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/__test/unauthenticated')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_forbidden_returns_403(): void
    {
        $this->getJson('/api/v1/__test/forbidden')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_rate_limited_requests_return_429_with_retry_after(): void
    {
        $this->getJson('/api/v1/__test/throttled')->assertOk();
        $this->getJson('/api/v1/__test/throttled')->assertOk();

        $this->getJson('/api/v1/__test/throttled')
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');
    }

    public function test_valid_client_request_id_is_echoed(): void
    {
        $this->withHeaders(['X-Request-Id' => 'client-req-12345'])
            ->getJson('/api/v1/health')
            ->assertHeader('X-Request-Id', 'client-req-12345');
    }

    public function test_malformed_client_request_id_is_replaced(): void
    {
        $response = $this->withHeaders(['X-Request-Id' => 'bad id!'])->getJson('/api/v1/health');

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/',
            (string) $response->headers->get('X-Request-Id')
        );
    }

    public function test_paginated_responses_include_pagination_meta(): void
    {
        $this->getJson('/api/v1/__test/paginated')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 5)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.last_page', 3)
            ->assertJsonPath('meta.pagination.has_more_pages', true);
    }
}
