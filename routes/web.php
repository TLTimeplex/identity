<?php

use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\OAuth\ConsentController;
use App\Http\Controllers\OAuth\HydraLoginController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    //Route::middleware('can:identity.admin.view')->group(function () {
        Route::get('admin/users', [UsersController::class, 'index'])->name('admin_users_index');
        Route::get('admin/users/{user}', [UsersController::class, 'show'])->name('admin_users_show');
        Route::patch('admin/users/{user}', [UsersController::class, 'update'])->name('admin_users_update');
        Route::delete('admin/users/{user}', [UsersController::class, 'destroy'])->name('admin_users_destroy');
        Route::delete('admin/users/{user}/sessions', [UsersController::class, 'destroySessions'])->name('admin_users_sessions_destroy');
        Route::delete('admin/users/{user}/two-factor', [UsersController::class, 'destroyTwoFactor'])->name('admin_users_two_factor_destroy');
        Route::delete('admin/users/{user}/passkeys', [UsersController::class, 'destroyPasskeys'])->name('admin_users_passkeys_destroy');
        Route::delete('admin/users/{user}/passkeys/{passkey}', [UsersController::class, 'destroyPasskey'])->name('admin_users_passkey_destroy');
    //});
});



Route::get('/oauth/login', HydraLoginController::class)
    ->name('oauth.login');

Route::get('/oauth/consent', ConsentController::class)
    ->middleware('auth')
    ->name('oauth.consent');

Route::post('/oauth/consent/accept', [
    ConsentController::class,
    'accept',
])
    ->middleware('auth')
    ->name('oauth.consent.accept');

Route::post('/oauth/consent/reject', [
    ConsentController::class,
    'reject',
])
    ->middleware('auth')
    ->name('oauth.consent.reject');

require __DIR__.'/settings.php';
