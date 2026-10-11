<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocaleFromHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global (runs before routing): responses to unmatched routes and wrong
        // HTTP methods (404/405) also carry X-Request-Id and Content-Language.
        $middleware->prepend([
            AssignRequestId::class,
            SetLocaleFromHeader::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // Uses the "api" limiter defined in AppServiceProvider.
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(
            fn (Throwable $e, Request $request) => (new ApiExceptionRenderer)($e, $request)
        );
    })->create();
