<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\WebAuthnController;
use App\Http\Controllers\Auth\JwksController;

Route::get('/health', fn () => ['ok' => true])->name('health');

// Public auth endpoints
Route::prefix('auth')->group(function () {
    // Registration + login
    Route::post('/register', [RegisterController::class, 'store'])->name('auth.register');
    Route::post('/login', [LoginController::class, 'password'])->name('auth.login');

    // Cookie session login/logout (for SPA same-site)
    Route::post('/session/login', [SessionController::class, 'login'])->name('auth.session.login');
    Route::post('/session/logout', [SessionController::class, 'logout'])->name('auth.session.logout');

    // Magic link (stubs)
    Route::post('/magic/request', [MagicLinkController::class, 'requestLink'])->name('auth.magic.request');
    Route::post('/magic/verify', [MagicLinkController::class, 'verify'])->name('auth.magic.verify');

    // OAuth (stubs)
    Route::get('/oauth/redirect/{provider}', [OAuthController::class, 'redirect'])->name('auth.oauth.redirect');
    Route::get('/oauth/callback/{provider}', [OAuthController::class, 'callback'])->name('auth.oauth.callback');

    // WebAuthn (stubs)
    Route::post('/webauthn/options', [WebAuthnController::class, 'options'])->name('auth.webauthn.options');
    Route::post('/webauthn/register', [WebAuthnController::class, 'register'])->name('auth.webauthn.register');
    Route::post('/webauthn/verify', [WebAuthnController::class, 'verify'])->name('auth.webauthn.verify');

    // JWKS (empty set by default)
    Route::get('/.well-known/jwks.json', [JwksController::class, 'index'])->name('auth.jwks');
});

// Protected endpoints (Sanctum: token or cookie)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [SessionController::class, 'me'])->name('auth.me');

    // Personal Access Tokens management
    Route::get('/auth/tokens', [TokenController::class, 'index'])->name('auth.tokens.index');
    Route::post('/auth/tokens', [TokenController::class, 'store'])->name('auth.tokens.store');
    Route::delete('/auth/tokens/{id}', [TokenController::class, 'destroy'])->name('auth.tokens.destroy');
});
