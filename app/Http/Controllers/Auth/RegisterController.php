<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        event(new Registered($user));

        return (new UserResource($user))
            ->additional(['meta' => ['message' => 'Registered successfully']])
            ->response()
            ->setStatusCode(201);
    }
}
