<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_can_register_user_via_api(): void
    {
        $payload = [
            'name' => 'New User',
            'email' => 'new.user@example.com',
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'new.user@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'new.user@example.com',
        ]);
    }

    public function test_can_login_with_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'verified@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', 'verified@example.com');
    }

    public function test_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'verify.me@example.com',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertStringContainsString(
            '/home',
            $response->headers->get('Location')
        );

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
