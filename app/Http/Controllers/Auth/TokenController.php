<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    /**
     * Issue a Personal Access Token (Sanctum) using email/password.
     */
    public function store(LoginPasswordRequest $request): JsonResponse
    {
        $credentials = $request->only('email','password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user  = $request->user();
        $name  = $request->input('device_name', $request->userAgent() ?? 'api');
        $token = $user->createToken($name);

        return response()->json([
            'token' => $token->plainTextToken,
        ], 201);
    }

    /**
     * List current user's tokens.
     */
    public function index(Request $request)
    {
        return $request->user()->tokens()->get(['id','name','last_used_at','created_at']);
    }

    /**
     * Revoke a specific token by id.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $id)->delete();

        return $deleted
            ? response()->json(['deleted' => true])
            : response()->json(['deleted' => false], 404);
    }
}
