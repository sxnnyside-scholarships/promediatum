<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Registration (single-user guard applied in controller + middleware)
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->middleware('registration.open')
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('registration.open');

    // Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Forgot Password (recovery code based)
    Route::get('forgot-password', [PasswordRecoveryController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordRecoveryController::class, 'verify'])
        ->name('password.verify');

    // Reset Password (after code verification)
    Route::get('reset-password', [PasswordRecoveryController::class, 'showReset'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordRecoveryController::class, 'reset'])
        ->name('password.store');

    // Two-Factor Challenge (when logging in with 2FA enabled)
    Route::get('two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.login');

    Route::post('two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'store'])
        ->name('two-factor.challenge');

    Route::post('two-factor-challenge/cancel', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'destroy'])
        ->name('two-factor.cancel');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Session unlock (view and submit)
    Route::get('unlock', [AuthenticatedSessionController::class, 'showUnlock'])
        ->name('unlock');

    Route::post('unlock', [AuthenticatedSessionController::class, 'unlock'])
        ->name('session.unlock');

    // Session lock
    Route::post('lock', [AuthenticatedSessionController::class, 'lock'])
        ->name('session.lock');

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
