<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
            'remember' => ['sometimes','boolean'],
        ]);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $request->boolean('remember'))) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $request->session()->regenerate();

        return response()->json(['message' => 'Session login OK']);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
