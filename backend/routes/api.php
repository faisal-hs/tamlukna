<?php

use Illuminate\Support\Facades\Route;

/*
 * Laravel applies the "api" prefix and middleware group to this file.
 * Each API version lives in its own file; v1 → /api/v1/*.
 */
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    require __DIR__.'/api/v1.php';

    // Unknown /api/v1/* paths still pass through the API middleware
    // (locale, request ID) and return the standard JSON 404.
    Route::fallback(fn () => abort(404));
});
