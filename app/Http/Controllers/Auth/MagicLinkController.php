<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function requestLink(Request $request)
    {
        // TODO: generate signed URL and email it to the user
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function verify(Request $request)
    {
        // TODO: validate signature and login the user
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
