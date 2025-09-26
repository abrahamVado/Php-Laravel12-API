<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
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

        $request->session()->regenerate();

        return response()->json(['message' => 'Login OK (session established)']);
    }
}
