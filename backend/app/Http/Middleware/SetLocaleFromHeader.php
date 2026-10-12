<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the first supported language from Accept-Language (e.g. "ar-SA" → "ar"),
 * falling back to config('tamlukna.locales.default').
 *
 * The fallback deliberately does not read config('app.locale'): App::setLocale()
 * overwrites that value, so a previous request's language would leak into the next
 * one in long-running processes (tests, queue workers).
 */
final class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<int, string> $supported */
        $supported = config('tamlukna.locales.supported', ['ar', 'en']);
        $locale = (string) config('tamlukna.locales.default', 'ar');

        foreach ($request->getLanguages() as $candidate) {
            $primary = strtolower(explode('_', str_replace('-', '_', $candidate))[0]);

            if (in_array($primary, $supported, true)) {
                $locale = $primary;
                break;
            }
        }

        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
