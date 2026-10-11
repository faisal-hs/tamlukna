<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Readiness check: can the application reach its dependencies?
 * Failure details are logged, never returned.
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => DB::select('select 1')),
            'redis' => $this->check('redis', fn () => Redis::connection()->ping()),
        ];

        $healthy = ! in_array('fail', $checks, true);

        $data = [
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => now()->toIso8601ZuluString(),
        ];

        return $healthy
            ? ApiResponse::success($data, (string) __('api.health.ok'))
            : ApiResponse::error(
                message: (string) __('api.health.degraded'),
                status: 503,
                code: 'service_unavailable',
                data: $data,
            );
    }

    private function check(string $name, Closure $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $e) {
            Log::warning('Health check failed', ['check' => $name, 'exception' => $e::class]);

            return 'fail';
        }
    }
}
