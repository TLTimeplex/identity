<?php

use App\Http\Controllers\OAuth\ConsentController;
use App\Http\Controllers\OAuth\HydraLoginController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
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
