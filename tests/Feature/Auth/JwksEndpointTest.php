<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwksEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_configured_keys(): void
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $details = openssl_pkey_get_details($resource);
        $publicKey = $details['key'] ?? '';

        config([
            'jwks.keys' => [[
                'kid' => 'test-key',
                'alg' => 'RS256',
                'use' => 'sig',
                'public_pem' => $publicKey,
            ]],
        ]);

        $response = $this->getJson('/api/auth/.well-known/jwks.json');

        $response->assertOk();
        $response->assertJsonCount(1, 'keys');
        $response->assertJsonFragment(['kid' => 'test-key']);
        $response->assertJsonStructure([
            'keys' => [[
                'kty',
                'kid',
                'alg',
                'use',
                'n',
                'e',
            ]],
        ]);
    }
}
