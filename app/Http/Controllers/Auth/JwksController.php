<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class JwksController extends Controller
{
    public function index(): JsonResponse
    {
        $configured = config('jwks.keys', []);

        $jwks = [];
        foreach ($configured as $k) {
            $pem = $k['public_pem'] ?? null;
            if (! $pem) {
                continue;
            }

            // Allow file path or inline PEM.
            $pemString = is_file($pem) ? @file_get_contents($pem) : $pem;
            if (! $pemString) {
                continue;
            }

            $res = @openssl_pkey_get_public($pemString);
            if ($res === false) {
                // Skip invalid keys rather than 500 the whole endpoint.
                continue;
            }

            $details = openssl_pkey_get_details($res);
            if (! $details || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
                // This sample focuses on RSA (kty=RSA).
                // Add EC support later if you use ES256/384.
                continue;
            }

            $n = $details['rsa']['n'] ?? null; // modulus (binary)
            $e = $details['rsa']['e'] ?? null; // exponent (binary)
            if (! $n || ! $e) {
                continue;
            }

            $jwks[] = [
                'kty' => 'RSA',
                'use' => $k['use'] ?? 'sig',
                'alg' => $k['alg'] ?? 'RS256',
                'kid' => $k['kid'] ?? sha1($pemString),
                'n'   => $this->b64url($n),
                'e'   => $this->b64url($e),
            ];
        }

        $payload = ['keys' => array_values($jwks)];
        $etag = sha1(json_encode($payload));

        return response()
            ->json($payload)
            ->setEtag($etag)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300');
    }

    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
