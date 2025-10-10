<?php

namespace Tests\Feature\Cors;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CorsHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        //1.- Disable CSRF checks so the JSON login request can succeed inside the test harness.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        //2.- Apply the same origin configuration that production will use for SPA credential sharing.
        config()->set('cors.allowed_origins', ['http://localhost:3000']);
        config()->set('cors.supports_credentials', true);
    }

    public function test_login_response_uses_specific_origin_header(): void
    {
        //1.- Create a verified user with a known password to authenticate against the API route.
        $user = User::factory()->create([
            'email' => 'spa@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        //2.- Exercise the login endpoint with the SPA origin header to trigger the CORS middleware.
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        //3.- Assert the response succeeded and exposes the specific CORS headers required for credentialed requests.
        $response->assertOk();
        $this->assertSame('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }
}
