<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class OAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);

        return $driver->stateless()->redirect();
    }

    public function callback(string $provider): JsonResponse
    {
        $driver = $this->driver($provider);

        try {
            $oauthUser = $driver->stateless()->user();
        } catch (Throwable $exception) {
            Log::warning('OAuth callback failed', [
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            $status = $exception instanceof InvalidStateException ? 400 : 500;

            return response()->json([
                'message' => 'Unable to complete OAuth login.',
            ], $status);
        }

        $email = Str::lower((string) $oauthUser->getEmail());
        if ($email === '') {
            return response()->json([
                'message' => 'OAuth provider did not return an email address.',
            ], 422);
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $oauthUser->getName() ?: $oauthUser->getNickname() ?: $email;
            $user->password = Str::password(32);
            $user->email_verified_at = now();
            $user->save();
        }

        if ($user->wasRecentlyCreated && empty($user->name)) {
            $user->forceFill(['name' => $oauthUser->getName() ?: $user->email])->save();
        }

        $identity = $user->identities()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => (string) $oauthUser->getId(),
            ],
            [
                'provider_email' => $email,
                'data' => array_filter([
                    'name' => $oauthUser->getName(),
                    'nickname' => $oauthUser->getNickname(),
                    'avatar' => $oauthUser->getAvatar(),
                ]),
            ]
        );

        if (! $identity->wasRecentlyCreated) {
            $identity->touch();
        }

        Auth::guard('web')->login($user, true);

        /** @var NewAccessToken $token */
        $token = $user->createToken('oauth-'.$provider);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'provider' => $provider,
        ]);
    }

    protected function driver(string $provider): Provider
    {
        $supported = Config::get('services.socialite.providers', []);
        if (! in_array($provider, $supported, true)) {
            abort(404, 'Unsupported OAuth provider.');
        }

        return Socialite::driver($provider);
    }
}
