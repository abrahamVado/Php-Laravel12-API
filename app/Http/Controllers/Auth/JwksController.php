<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class JwksController extends Controller
{
    public function index()
    {
        // Placeholder JWKS; useful if you later issue JWTs and need to expose public keys.
        return response()->json(['keys' => []]);
    }
}
