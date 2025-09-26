<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class OAuthController extends Controller
{
    public function redirect(string $provider)
    {
        // TODO: return Socialite::driver($provider)->redirect();
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function callback(string $provider)
    {
        // TODO: handle Socialite callback and login/create user
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
