<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebAuthnCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebAuthnController extends Controller
{
    private const CHALLENGE_TTL = 300;

    public function options(Request $request): JsonResponse
    {
        $type = $request->string('type')->lower()->value() ?: 'login';

        if (! in_array($type, ['login', 'register'], true)) {
            throw ValidationException::withMessages([
                'type' => __('The type must be either login or register.'),
            ]);
        }

        if ($type === 'register') {
            $user = $request->user();
            if (! $user instanceof User) {
                abort(401, 'Authentication required for registration options.');
            }

            $challenge = $this->makeChallenge('register', (string) $user->getKey());

            $response = [
                'type' => 'register',
                'publicKey' => [
                    'rp' => [
                        'name' => Config::get('app.name', 'Laravel API'),
                        'id' => parse_url(Config::get('app.url'), PHP_URL_HOST) ?: 'localhost',
                    ],
                    'user' => [
                        'id' => $this->toBase64Url((string) $user->getAuthIdentifier()),
                        'name' => $user->email,
                        'displayName' => $user->name ?? $user->email,
                    ],
                    'challenge' => $challenge,
                    'pubKeyCredParams' => [
                        ['type' => 'public-key', 'alg' => -7], // ES256
                        ['type' => 'public-key', 'alg' => -257], // RS256
                    ],
                    'timeout' => 60000,
                    'attestation' => 'none',
                    'excludeCredentials' => $user->webauthnCredentials
                        ->map(fn (WebAuthnCredential $credential) => [
                            'type' => 'public-key',
                            'id' => $credential->credential_id,
                        ])->all(),
                ],
            ];

            return response()->json($response);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', Str::lower($validated['email']))->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('No user found for the supplied email.'),
            ]);
        }

        $challenge = $this->makeChallenge('login', (string) $user->getKey());

        $response = [
            'type' => 'login',
            'publicKey' => [
                'challenge' => $challenge,
                'timeout' => 60000,
                'rpId' => parse_url(Config::get('app.url'), PHP_URL_HOST) ?: 'localhost',
                'allowCredentials' => $user->webauthnCredentials
                    ->map(fn (WebAuthnCredential $credential) => [
                        'id' => $credential->credential_id,
                        'type' => 'public-key',
                        'transports' => $credential->transports ?? [],
                    ])->all(),
                'userVerification' => 'preferred',
            ],
        ];

        return response()->json($response);
    }

    public function register(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401, 'Authentication required to register credentials.');
        }

        $payload = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'type' => ['required', 'in:public-key'],
            'name' => ['nullable', 'string', 'max:255'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.attestationObject' => ['required', 'string'],
            'publicKey' => ['required', 'string'],
            'signCount' => ['nullable', 'integer', 'min:0'],
            'transports' => ['nullable', 'array'],
            'transports.*' => ['string'],
        ]);

        $challenge = $this->pullChallenge('register', (string) $user->getKey());
        $clientData = $this->decodeClientData($payload['response']['clientDataJSON']);

        if (($clientData['type'] ?? null) !== 'webauthn.create') {
            throw ValidationException::withMessages([
                'response.clientDataJSON' => __('Invalid client data type.'),
            ]);
        }

        if (! isset($clientData['challenge']) || ! hash_equals($challenge, $clientData['challenge'])) {
            throw ValidationException::withMessages([
                'response.clientDataJSON' => __('Challenge mismatch.'),
            ]);
        }

        $credential = $user->webauthnCredentials()->updateOrCreate(
            ['credential_id' => $payload['id']],
            [
                'name' => $payload['name'] ?? null,
                'public_key' => $payload['publicKey'],
                'sign_count' => $payload['signCount'] ?? 0,
                'transports' => $payload['transports'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'id' => $credential->credential_id,
            'name' => $credential->name,
        ], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['required', 'string'],
            'type' => ['required', 'in:public-key'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.authenticatorData' => ['required', 'string'],
            'response.signature' => ['required', 'string'],
            'signCount' => ['nullable', 'integer', 'min:0'],
        ]);

        $credential = WebAuthnCredential::query()->where('credential_id', $payload['id'])->first();
        if (! $credential) {
            throw ValidationException::withMessages([
                'id' => __('Credential not found.'),
            ]);
        }

        $challenge = $this->pullChallenge('login', (string) $credential->user_id);
        $clientData = $this->decodeClientData($payload['response']['clientDataJSON']);

        if (($clientData['type'] ?? null) !== 'webauthn.get') {
            throw ValidationException::withMessages([
                'response.clientDataJSON' => __('Invalid client data type.'),
            ]);
        }

        if (! isset($clientData['challenge']) || ! hash_equals($challenge, $clientData['challenge'])) {
            throw ValidationException::withMessages([
                'response.clientDataJSON' => __('Challenge mismatch.'),
            ]);
        }

        $signCount = $payload['signCount'] ?? 0;
        if ($signCount < $credential->sign_count) {
            throw ValidationException::withMessages([
                'signCount' => __('Sign count was lower than expected.'),
            ]);
        }

        $credential->forceFill([
            'sign_count' => $signCount,
            'last_used_at' => now(),
        ])->save();

        $user = $credential->user;

        Auth::guard('web')->login($user, true);
        $token = $user->createToken('webauthn');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    private function makeChallenge(string $type, string $key): string
    {
        $challenge = $this->toBase64Url(random_bytes(32));

        Cache::put($this->cacheKey($type, $key), $challenge, self::CHALLENGE_TTL);

        return $challenge;
    }

    private function pullChallenge(string $type, string $key): string
    {
        $cacheKey = $this->cacheKey($type, $key);
        $challenge = Cache::pull($cacheKey);

        if (! is_string($challenge) || $challenge === '') {
            throw ValidationException::withMessages([
                'challenge' => __('The WebAuthn challenge has expired or is invalid.'),
            ]);
        }

        return $challenge;
    }

    private function decodeClientData(string $encoded): array
    {
        $decoded = json_decode($this->fromBase64Url($encoded), true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'response.clientDataJSON' => __('Unable to parse client data.'),
            ]);
        }

        return $decoded;
    }

    private function toBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function fromBase64Url(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }

    private function cacheKey(string $type, string $key): string
    {
        return "webauthn:{$type}:{$key}";
    }
}
