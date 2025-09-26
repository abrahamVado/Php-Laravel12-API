<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Issue a Personal Access Token (Sanctum) using email/password.
     */
    public function store(LoginPasswordRequest $request): JsonResponse
    {
        $credentials = $request->only('email','password');

        /** @var \App\Models\User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $exception = ValidationException::withMessages([
                'email' => ['Email verification required.'],
            ]);
            $exception->status = 403;

            throw $exception;
        }

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
