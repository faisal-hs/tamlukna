<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Properties\MyPropertyController;
use App\Http\Controllers\Api\V1\Properties\PropertyController;
use App\Http\Controllers\Api\V1\Properties\PropertyHistoryController;
use App\Http\Controllers\Api\V1\Properties\PropertyStatusController;
use Illuminate\Support\Facades\Route;

// Listing references only (TML-XXXXXXXX); anything else falls through to the JSON 404.
Route::pattern('property', 'TML-[0-9A-HJKMNP-TV-Z]{8}');

Route::get('health', HealthController::class)->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

        // Signed link from the verification email; works without a bearer token.
        Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->whereUlid('id')
            ->where('hash', '[a-f0-9]{40}')
            ->name('verification.verify');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

// Public (optional bearer token): listing details.
Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('me', MeController::class)->name('me');
    Route::get('me/properties', MyPropertyController::class)->name('me.properties');

    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::patch('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::post('properties/{property}/submit', [PropertyStatusController::class, 'submit'])->name('properties.submit');
    Route::post('properties/{property}/pause', [PropertyStatusController::class, 'pause'])->name('properties.pause');
    Route::post('properties/{property}/resume', [PropertyStatusController::class, 'resume'])->name('properties.resume');
    Route::get('properties/{property}/history', PropertyHistoryController::class)->name('properties.history');
});
