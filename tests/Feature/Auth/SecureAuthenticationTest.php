<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecureAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_dispatches_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Str0ngP@ssword!',
        ]);

        $response->assertCreated();

        $user = User::whereEmail('jane@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_normalizes_mixed_case_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Janet Doe',
            'email' => ' Mixed.Case@Example.COM ',
            'password' => 'Str0ngP@ssword!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'mixed.case@example.com',
        ]);
    }

    public function test_session_login_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonValidationErrors('email');
    }

    public function test_session_login_succeeds_for_verified_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('meta.message', 'Session login OK');
    }

    public function test_session_login_accepts_mixed_case_email(): void
    {
        $user = User::factory()->create([
            'email' => 'casey@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/session/login', [
            'email' => '  Casey@Example.COM ',
            'password' => 'Secret123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('meta.message', 'Session login OK');
    }

    public function test_token_login_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'api@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/tokens', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonValidationErrors('email');
    }

    public function test_token_login_returns_token_for_verified_user(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/tokens', [
            'email' => $user->email,
            'password' => 'Secret123!',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'phpunit',
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_token_login_accepts_mixed_case_email(): void
    {
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/tokens', [
            'email' => ' Token@Example.COM ',
            'password' => 'Secret123!',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['token']);
    }

    public function test_secure_routes_require_authentication(): void
    {
        $this->getJson('/api/secure/dashboard')->assertUnauthorized();
        $this->getJson('/api/secure/users')->assertUnauthorized();
        $this->getJson('/api/secure/profile')->assertUnauthorized();
        $this->getJson('/api/secure/logs')->assertUnauthorized();
        $this->getJson('/api/secure/errors')->assertUnauthorized();
    }

    public function test_secure_routes_return_user_payload_for_authenticated_requests(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        foreach (['dashboard', 'users', 'profile', 'logs', 'errors'] as $section) {
            $this->getJson("/api/secure/{$section}")
                ->assertOk()
                ->assertJsonPath('data.id', $user->id)
                ->assertJsonPath('meta.section', $section)
                ->assertJsonPath('meta.message', 'Authenticated access granted.');
        }
    }

    public function test_authenticated_users_can_manage_personal_access_tokens(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $token = $user->createToken('existing');

        $this->getJson('/api/auth/tokens')
            ->assertOk()
            ->assertJsonFragment(['name' => 'existing']);

        $this->deleteJson('/api/auth/tokens/'.$token->accessToken->id)
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }
}
