<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JSON Web Key Set Configuration
    |--------------------------------------------------------------------------
    |
    | Define the keys exposed through the /.well-known/jwks.json endpoint.
    | Provide inline PEM values or file paths. Environment variables are
    | supported for the first entry out of the box.
    |
    */

    'keys' => array_values(array_filter([
        env('JWKS_PUBLIC_KEY') ? [
            'kid' => env('JWKS_KID', 'local-signing-key'),
            'alg' => env('JWKS_ALG', 'RS256'),
            'use' => env('JWKS_USE', 'sig'),
            'public_pem' => env('JWKS_PUBLIC_KEY'),
        ] : null,
    ])),
];
