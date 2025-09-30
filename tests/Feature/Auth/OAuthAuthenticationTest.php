<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OAuthAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_to_provider(): void
    {
        config(['services.socialite.providers' => ['github']]);

        $redirect = new \Illuminate\Http\RedirectResponse('https://github.com/login/oauth/authorize');

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('redirect')->once()->andReturn($redirect);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($driver);

        $response = $this->get('/api/auth/oauth/redirect/github');

        $response->assertStatus(302);
        $response->assertRedirect('https://github.com/login/oauth/authorize');
    }

    public function test_callback_creates_user_and_logs_in(): void
    {
        config(['services.socialite.providers' => ['github']]);

        $oauthUser = new SocialiteUser();
        $oauthUser->map([
            'id' => '12345',
            'nickname' => 'octocat',
            'name' => 'Octo Cat',
            'email' => 'octo@example.com',
            'avatar' => 'https://avatars.githubusercontent.com/u/1',
        ]);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($oauthUser);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($driver);

        $response = $this->getJson('/api/auth/oauth/callback/github');

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
            'provider',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'octo@example.com',
            'name' => 'Octo Cat',
        ]);

        $this->assertDatabaseHas('user_identities', [
            'provider' => 'github',
            'provider_id' => '12345',
            'provider_email' => 'octo@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_callback_failure_returns_bad_request(): void
    {
        config(['services.socialite.providers' => ['github']]);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andThrow(new InvalidStateException());

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($driver);

        $response = $this->getJson('/api/auth/oauth/callback/github');

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Unable to complete OAuth login.']);
    }

    public function test_redirect_with_unsupported_provider_returns_not_found(): void
    {
        config(['services.socialite.providers' => ['github']]);

        $response = $this->get('/api/auth/oauth/redirect/mars', ['Accept' => 'application/json']);

        $response->assertNotFound();
        $response->assertJson(['message' => 'Unsupported OAuth provider.']);
    }

    public function test_callback_with_unsupported_provider_returns_not_found(): void
    {
        config(['services.socialite.providers' => ['github']]);

        $response = $this->getJson('/api/auth/oauth/callback/mars');

        $response->assertNotFound();
        $response->assertJson(['message' => 'Unsupported OAuth provider.']);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
