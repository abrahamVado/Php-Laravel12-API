<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\WebAuthnController;
use App\Http\Controllers\Auth\JwksController;
use App\Http\Controllers\Secure\SecurePageController;

Route::get('/health', fn () => ['ok' => true])->name('health');

// Public auth endpoints
Route::prefix('auth')->group(function () {
    // Registration + login
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware(['throttle:register'])
        ->name('auth.register');
    Route::post('/login', [LoginController::class, 'password'])
        ->middleware(['throttle:login', 'web'])
        ->name('auth.login');

    // Cookie session login/logout (for SPA same-site)
    Route::post('/session/login', [SessionController::class, 'login'])
        ->middleware(['throttle:login', 'web'])
        ->name('auth.session.login');
    Route::post('/session/logout', [SessionController::class, 'logout'])
        ->middleware('web')
        ->name('auth.session.logout');


    // Magic link (stubs)
    Route::post('/magic/request', [MagicLinkController::class, 'requestLink'])
        ->middleware('web')
        ->name('auth.magic.request');
    Route::post('/magic/verify', [MagicLinkController::class, 'verify'])
        ->middleware('web')
        ->name('auth.magic.verify');

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
    Route::delete('/auth/tokens/{id}', [TokenController::class, 'destroy'])->name('auth.tokens.destroy');

    // Secure application areas (dashboard, profile, logs, etc.)
    Route::prefix('secure')->group(function () {
        Route::get('/dashboard', [SecurePageController::class, 'dashboard'])
            ->name('secure.dashboard');
        Route::get('/users', [SecurePageController::class, 'users'])
            ->name('secure.users');
        Route::get('/profile', [SecurePageController::class, 'profile'])
            ->name('secure.profile');
        Route::get('/logs', [SecurePageController::class, 'logs'])
            ->name('secure.logs');
        Route::get('/errors', [SecurePageController::class, 'errors'])
            ->name('secure.errors');
    });

    // Administrative CRUD endpoints
    Route::prefix('admin')->group(function () {
        Route::apiResource('roles', AdminRoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        Route::apiResource('users', AdminUserController::class);
        Route::apiResource('profiles', ProfileController::class);
        Route::apiResource('teams', TeamController::class);
        Route::apiResource('settings', SettingController::class);
    });
});

// Personal access token issuance for first-party & third-party clients
Route::post('/auth/tokens', [TokenController::class, 'store'])
    ->middleware('throttle:login')
    ->name('auth.tokens.store');
