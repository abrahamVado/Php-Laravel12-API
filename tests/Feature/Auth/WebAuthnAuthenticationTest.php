<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebAuthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebAuthnAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_authenticated_user_can_request_registration_options(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/webauthn/options', [
            'type' => 'register',
        ]);

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) {
            $json->where('type', 'register')
                ->has('publicKey.challenge')
                ->has('publicKey.rp.name')
                ->has('publicKey.user.id');
        });
    }

    public function test_user_can_register_credential_and_sign_in(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $optionsResponse = $this->postJson('/api/auth/webauthn/options', [
            'type' => 'register',
        ])->assertOk();

        $challenge = $optionsResponse->json('publicKey.challenge');
        $credentialId = $this->toBase64Url('credential-123');

        $clientDataRegister = $this->toBase64Url(json_encode([
            'type' => 'webauthn.create',
            'challenge' => $challenge,
            'origin' => config('app.url'),
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR));

        $registerResponse = $this->postJson('/api/auth/webauthn/register', [
            'id' => $credentialId,
            'rawId' => $credentialId,
            'type' => 'public-key',
            'name' => 'Laptop Key',
            'publicKey' => base64_encode('fake-public-key'),
            'signCount' => 0,
            'transports' => ['internal'],
            'response' => [
                'clientDataJSON' => $clientDataRegister,
                'attestationObject' => $this->toBase64Url('attestation'),
            ],
        ]);

        $registerResponse->assertCreated();
        $this->assertDatabaseHas('webauthn_credentials', [
            'credential_id' => $credentialId,
            'user_id' => $user->id,
        ]);

        $optionsLogin = $this->postJson('/api/auth/webauthn/options', [
            'type' => 'login',
            'email' => $user->email,
        ])->assertOk();

        $loginChallenge = $optionsLogin->json('publicKey.challenge');

        $clientDataLogin = $this->toBase64Url(json_encode([
            'type' => 'webauthn.get',
            'challenge' => $loginChallenge,
            'origin' => config('app.url'),
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR));

        $verifyResponse = $this->postJson('/api/auth/webauthn/verify', [
            'id' => $credentialId,
            'type' => 'public-key',
            'signCount' => 1,
            'response' => [
                'clientDataJSON' => $clientDataLogin,
                'authenticatorData' => $this->toBase64Url('authenticator'),
                'signature' => $this->toBase64Url('signature'),
            ],
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJsonStructure([
            'token',
            'user' => ['id', 'email'],
        ]);

        $this->assertAuthenticated();

        $credential = WebAuthnCredential::where('credential_id', $credentialId)->firstOrFail();
        $this->assertEquals(1, $credential->sign_count);
        $this->assertNotNull($credential->last_used_at);
    }

    private function toBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
