<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    /**
     * Attempts password auth and establishes a session (cookie-based).
     * For token auth, call TokenController@store instead.
     */
    public function password(LoginPasswordRequest $request): JsonResponse
    {
        $guard = config('fortify.guard', config('auth.defaults.guard', 'web'));

        // Normalize credentials
        $email = Str::of((string) $request->input('email'))->trim()->lower()->toString();
        $credentials = [
            'email'    => $email,
            'password' => (string) $request->input('password'),
        ];
        $remember = (bool) $request->boolean('remember', false);

        // Simple IP+email throttle key
        $throttleKey = Str::lower($email).'|'.$request->ip();

        // Rate limit: 5 tries / minute (tweak as needed)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => [__('Too many login attempts. Try again in :seconds seconds.', ['seconds' => $seconds])],
            ])->status(429);
        }

        Auth::shouldUse($guard);

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => [__('Invalid credentials.')],
            ]);
        }

        // Successful auth; clear attempts and rotate session ID
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Require email verification when applicable
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $ex = ValidationException::withMessages([
                'email' => [__('Email verification required.')],
            ]);
            $ex->status = 403;
            throw $ex;
        }

        // Optional: deny inactive/blocked accounts (uncomment if you have such columns)
        // if (property_exists($user, 'is_active') && ! $user->is_active) {
        //     Auth::logout();
        //     $request->session()->invalidate();
        //     $request->session()->regenerateToken();
        //     throw ValidationException::withMessages([
        //         'email' => [__('Your account is disabled.')],
        //     ])->status(403);
        // }

        return (new UserResource($user))
            ->additional(['meta' => ['message' => 'Login OK (session established)']])
            ->response();
    }
}
