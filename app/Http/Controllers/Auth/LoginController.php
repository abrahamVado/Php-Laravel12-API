<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Attempts password auth and establishes a session (cookie-based).
     * For token auth, call TokenController@store instead.
     */
    public function password(LoginPasswordRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $exception = ValidationException::withMessages([
                'email' => ['Email verification required.'],
            ]);
            $exception->status = 403;

            throw $exception;
        }

        $request->session()->regenerate();

        return response()->json(['message' => 'Login OK (session established)']);
    }
}
