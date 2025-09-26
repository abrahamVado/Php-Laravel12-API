<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebAuthnController extends Controller
{
    public function options(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function register(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function verify(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
