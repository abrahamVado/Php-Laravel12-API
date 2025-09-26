<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLoginToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MagicLinkController extends Controller
{
    /**
     * Request a magic login link via email.
     */
    public function requestLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
            // optional redirect after login
            'redirect_to' => ['sometimes', 'string', 'max:2048'],
        ]);

        $email = Str::of($data['email'])->lower()->trim()->toString();
        $remember = (bool)($data['remember'] ?? false);
        $redirectTo = (string)($data['redirect_to'] ?? route('home'));

        // Throttle by email + IP
        $key = 'magiclink:' . sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => [__('Too many attempts. Try again in :seconds seconds.', ['seconds' => $seconds])],
            ])->status(429);
        }

        // Look up user, but do NOT reveal existence.
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        // Always consume an attempt to slow enumeration.
        RateLimiter::hit($key, 60);

        if ($user) {
            // Create a single-use token (hashed at rest)
            $plain = Str::random(64);
            $token = MagicLoginToken::create([
                'id'          => (string) Str::ulid(),
                'user_id'     => $user->getKey(),
                'token_hash'  => hash('sha256', $plain),
                'expires_at'  => now()->addMinutes(15),
                'ip'          => $request->ip(),
                'user_agent'  => (string) $request->userAgent(),
                'remember'    => $remember,
                'redirect_to' => $redirectTo,
            ]);

            // Build a *temporary signed* URL that includes id+t
            $url = URL::temporarySignedRoute(
                'auth.magic.verify',
                now()->addMinutes(15),
                [
                    'id' => $token->id,
                    't'  => $plain,
                ]
            );

            // Notify the user (queueable)
            $user->notify(new MagicLinkNotification($url));
        }

        // Generic response
        return response()->json([
            'message' => 'If your email is registered, a login link has been sent.',
        ]);
    }

    /**
     * Verify the magic link and log the user in.
     */
    public function verify(Request $request): JsonResponse
    {
        // Check URL signature *and* basic params
        if (! $request->hasValidSignature()) {
            throw ValidationException::withMessages([
                'link' => [__('This link is invalid or has expired.')],
            ])->status(422);
        }

        $request->validate([
            'id' => ['required', 'string'],
            't'  => ['required', 'string', 'min:40', 'max:128'],
        ]);

        /** @var MagicLoginToken|null $record */
        $record = MagicLoginToken::query()->find($request->string('id'));

        if (! $record) {
            throw ValidationException::withMessages([
                'link' => [__('This link is invalid or has expired.')],
            ])->status(422);
        }

        // Check expiry / usage / token match
        $expired = $record->expires_at->isPast();
        $used    = ! is_null($record->used_at);
        $match   = hash_equals($record->token_hash, hash('sha256', (string) $request->input('t')));

        if ($expired || $used || ! $match) {
            // Soft-fail: mark suspicious usage attempt if you want
            throw ValidationException::withMessages([
                'link' => [__('This link is invalid or has expired.')],
            ])->status(422);
        }

        /** @var User $user */
        $user = $record->user;

        // If your app requires verified emails, consider treating a successful
        // magic-link as verification OR block unverified users.
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            // Option A: treat as verification:
            $user->markEmailAsVerified();
            // Option B: block instead (comment previous line and uncomment next):
            // return response()->json(['message' => 'Please verify your email first.'], 403);
        }

        // Mark token as used (one-shot)
        $record->forceFill([
            'used_at'   => now(),
            'used_ip'   => $request->ip(),
            'used_ua'   => (string) $request->userAgent(),
        ])->save();

        // Optional: purge older outstanding tokens for this user
        MagicLoginToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '<', now()->subMinutes(5))
            ->delete();

        // Log the user in and rotate session ID
        Auth::login($user, (bool) $record->remember);
        $request->session()->regenerate();

        // Return JSON; include redirect target so the SPA can navigate
        return response()->json([
            'meta' => [
                'message' => 'Login OK (magic link)',
                'redirect_to' => $record->redirect_to ?? route('home'),
            ],
            'data' => [
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }
}
