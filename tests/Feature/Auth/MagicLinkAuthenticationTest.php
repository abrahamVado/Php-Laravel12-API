<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MagicLinkAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_magic_link_request_and_verify_flow(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->withHeader('User-Agent', 'PHPUnit Requester')
            ->postJson('/api/auth/magic/request', [
                'email' => $user->email,
                'remember' => true,
                'redirect_to' => 'https://spa.example/dashboard',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'If your email is registered, a login link has been sent.',
            ]);

        $linkData = $this->captureMagicLink($user);

        $record = DB::table('magic_login_tokens')->where('id', $linkData['params']['id'])->first();
        $this->assertNotNull($record, 'Magic login token was not persisted.');
        $this->assertSame($user->id, $record->user_id);
        $this->assertSame(hash('sha256', $linkData['params']['t']), $record->token_hash);
        $this->assertTrue((bool) $record->remember);
        $this->assertSame('https://spa.example/dashboard', $record->redirect_to);
        $this->assertSame('127.0.0.1', $record->ip);
        $this->assertNull($record->used_at);
        $this->assertNull($record->used_ip);

        $verifyResponse = $this->withHeader('User-Agent', 'PHPUnit Verifier')
            ->postJson($linkData['path'].'?'.$linkData['query'], [
                'id' => $linkData['params']['id'],
                't' => $linkData['params']['t'],
            ]);

        $verifyResponse
            ->assertOk()
            ->assertJsonPath('meta.message', 'Login OK (magic link)')
            ->assertJsonPath('meta.redirect_to', 'https://spa.example/dashboard')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertAuthenticatedAs($user);

        $record = DB::table('magic_login_tokens')->where('id', $linkData['params']['id'])->first();
        $this->assertNotNull($record->used_at);
        $this->assertSame('127.0.0.1', $record->used_ip);
    }

    public function test_magic_link_verification_fails_when_signature_expired(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->postJson('/api/auth/magic/request', [
            'email' => $user->email,
        ])->assertOk();

        $linkData = $this->captureMagicLink($user);

        Carbon::setTestNow($now->copy()->addMinutes(16));

        try {
            $this->postJson($linkData['path'].'?'.$linkData['query'], [
                'id' => $linkData['params']['id'],
                't' => $linkData['params']['t'],
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('link');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_magic_link_verification_fails_with_tampered_signature(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/auth/magic/request', [
            'email' => $user->email,
        ])->assertOk();

        $linkData = $this->captureMagicLink($user);

        $tamperedQuery = $linkData['params'];
        $tamperedQuery['signature'] = 'invalid-signature';

        $this->postJson($linkData['path'].'?'.Arr::query($tamperedQuery), [
            'id' => $linkData['params']['id'],
            't' => $linkData['params']['t'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('link');
    }

    public function test_magic_link_cannot_be_reused(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/auth/magic/request', [
            'email' => $user->email,
        ])->assertOk();

        $linkData = $this->captureMagicLink($user);

        $this->postJson($linkData['path'].'?'.$linkData['query'], [
            'id' => $linkData['params']['id'],
            't' => $linkData['params']['t'],
        ])->assertOk();

        $this->postJson($linkData['path'].'?'.$linkData['query'], [
            'id' => $linkData['params']['id'],
            't' => $linkData['params']['t'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('link');
    }

    private function captureMagicLink(User $user): array
    {
        $magicUrl = null;

        Notification::assertSentTo(
            $user,
            MagicLinkNotification::class,
            function ($notification) use ($user, &$magicUrl) {
                $magicUrl = $this->extractMagicLinkUrl($notification, $user);

                return ! empty($magicUrl);
            }
        );

        $this->assertNotNull($magicUrl, 'Magic link URL could not be captured from the notification.');

        $path = parse_url($magicUrl, PHP_URL_PATH) ?? '';
        $queryString = parse_url($magicUrl, PHP_URL_QUERY) ?? '';

        parse_str($queryString, $params);

        $this->assertArrayHasKey('id', $params);
        $this->assertArrayHasKey('t', $params);
        $this->assertArrayHasKey('expires', $params);
        $this->assertArrayHasKey('signature', $params);

        return [
            'url' => $magicUrl,
            'path' => $path,
            'query' => $queryString,
            'params' => $params,
        ];
    }

    private function extractMagicLinkUrl($notification, User $user): ?string
    {
        if (property_exists($notification, 'url') && ! empty($notification->url)) {
            return $notification->url;
        }

        if (method_exists($notification, 'toMail')) {
            $mailMessage = $notification->toMail($user);

            if (property_exists($mailMessage, 'actionUrl') && ! empty($mailMessage->actionUrl)) {
                return $mailMessage->actionUrl;
            }
        }

        return null;
    }
}
