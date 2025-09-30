# Yamato API Integration Guide

This document describes how the React + Next.js frontend should communicate with the Yamato Laravel API. It covers base URLs, authentication strategies, and the contract for each exposed endpoint.

> **Tip:** All examples assume JSON requests with the `Content-Type: application/json` header unless noted otherwise. Responses follow standard HTTP status codes and include JSON payloads. When a request fails validation, Laravel returns a `422` response with an `errors` object.

---

## Base URL & Versioning

| Environment | Base URL | Notes |
|-------------|----------|-------|
| Local (Docker stack) | `http://localhost/api` | When using the Yamato Docker stack, requests should be sent to the Nginx container at port 80. |
| Production | `https://<your-domain>/api` | Replace with the deployed host. All routes in this guide are relative to the `/api` prefix. |

There is currently no API versioning prefix. Breaking changes will be coordinated ahead of time.

---

## Authentication Overview

The API supports several authentication methods. Frontend clients can mix and match based on UX requirements.

| Method | Use Case | How it Works |
|--------|----------|--------------|
| **Session cookies** | First-party SPA hosted on the same domain. | Call `/auth/login` or `/auth/session/login` with credentials. Sanctum uses the `web` guard and issues an HTTP-only session cookie. Include `credentials: 'include'` in fetch/axios calls. |
| **Personal Access Tokens** | Native apps or SPAs on different domains. | Call `/auth/tokens` with email/password to receive a Bearer token. Send `Authorization: Bearer <token>` on subsequent requests. |
| **OAuth (Socialite)** | Sign in with GitHub or Google. | Redirect the user to `/auth/oauth/redirect/{provider}` and handle the JSON response returned by `/auth/oauth/callback/{provider}`. |
| **Magic links** | Passwordless login via email. | Request a link via `/auth/magic/request` and exchange it at `/auth/magic/verify`. |
| **WebAuthn (passkeys)** | FIDO2 authentication. | Use `/auth/webauthn/options`, `/auth/webauthn/register`, and `/auth/webauthn/verify` with the WebAuthn browser APIs. |

All authenticated requests that rely on Sanctum tokens or session cookies require the `Accept: application/json` header.

---

## Error Format

Most validation failures return HTTP `422` with a payload similar to:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Authentication failures may return `401`, `403`, or `422` depending on the scenario.

---

## Endpoint Reference

### Health Check

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/health` | None | Returns `{ "ok": true }`. Use for uptime checks. |

### Registration & Password Login

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/register` | None | Creates a user account. Rate-limited (`throttle:register`). |
| `POST` | `/auth/login` | None | Performs password login with session cookie response. Rate-limited (`throttle:login`). |

#### `/auth/register`

```jsonc
POST /api/auth/register
{
  "name": "Ada Lovelace",
  "email": "ada@example.com",
  "password": "secret123"
}
```

**201 Created**

```json
{
  "data": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "created_at": "2024-09-15T12:00:00Z",
    "updated_at": "2024-09-15T12:00:00Z"
  },
  "meta": {
    "message": "Registered successfully"
  }
}
```

#### `/auth/login`

```jsonc
POST /api/auth/login
{
  "email": "ada@example.com",
  "password": "secret123",
  "remember": true // optional
}
```

**200 OK**

```json
{
  "data": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "created_at": "2024-09-15T12:00:00Z",
    "updated_at": "2024-09-15T12:00:00Z"
  },
  "meta": {
    "message": "Login OK (session established)"
  }
}
```

Include `credentials: 'include'` on the frontend so the session cookie persists.

### Session Management (SPA-friendly)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/session/login` | None | Alternative login endpoint used by Sanctum SPA auth flow. Returns the same payload as `/auth/login`. |
| `POST` | `/auth/session/logout` | Session cookie | Logs out the current session and invalidates the cookie. |
| `GET` | `/auth/me` | Cookie or token | Returns the authenticated user resource. |

### Personal Access Tokens (Sanctum)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/tokens` | None | Exchange email/password for a Bearer token. Rate limited. |
| `GET` | `/auth/tokens` | Cookie or token | List active tokens for the current user. |
| `DELETE` | `/auth/tokens/{id}` | Cookie or token | Revoke a specific token by its database ID. |

#### `/auth/tokens` (issue token)

```jsonc
POST /api/auth/tokens
{
  "email": "ada@example.com",
  "password": "secret123",
  "device_name": "nextjs-app" // optional, defaults to User-Agent
}
```

**201 Created**

```json
{
  "token": "1|Uxj..." // send as Authorization: Bearer <token>
}
```

### OAuth Login (GitHub, Google)

1. Redirect the browser to `/api/auth/oauth/redirect/{provider}` (provider is `github` or `google`). The server responds with a 302 to the provider.
2. After provider consent, the provider redirects to `/api/auth/oauth/callback/{provider}` which returns JSON:

```json
{
  "token": "1|oauth-github...",
  "user": {
    "id": 5,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  },
  "provider": "github"
}
```

Store the returned token and treat it as a Sanctum Bearer token. The user is also logged into the `web` guard for cookie-based access.

Configure environment variables (`GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, etc.) so the redirect URIs resolve to `/api/auth/oauth/callback/{provider}`.

### Magic Link Authentication

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/magic/request` | None | Sends a passwordless login link if the email is registered. |
| `POST` | `/auth/magic/verify` | Signed link | Verifies the magic link, establishes a session, and returns the authenticated user with a redirect hint. |

#### Request a magic link

```jsonc
POST /api/auth/magic/request
{
  "email": "ada@example.com",
  "remember": true,           // optional, remember session
  "redirect_to": "/dashboard" // optional SPA redirect
}
```

**200 OK** – Message is always generic to avoid leaking account existence.

#### Verify a magic link

The signed URL contains `id` and `t` parameters. Exchange them with:

```jsonc
POST /api/auth/magic/verify?id=<id>&t=<token>
{
  "id": "01JC...",
  "t": "plaintext-token-from-link"
}
```

**200 OK**

```json
{
  "meta": {
    "message": "Login OK (magic link)",
    "redirect_to": "/dashboard"
  },
  "data": {
    "user": {
      "id": 5,
      "name": "Ada Lovelace",
      "email": "ada@example.com"
    }
  }
}
```

### WebAuthn (Passkeys)

These endpoints return JSON payloads ready to feed into the browser's WebAuthn APIs.

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/webauthn/options` | None (login) / Cookie (register) | For `type=login`, provide an email to receive assertion options. For `type=register`, the current user receives registration options. |
| `POST` | `/auth/webauthn/register` | Cookie | Finalizes credential registration. |
| `POST` | `/auth/webauthn/verify` | None | Verifies a login assertion, logs the user in, and returns a Sanctum token plus user payload. |

#### Get login options

```jsonc
POST /api/auth/webauthn/options
{
  "type": "login",
  "email": "ada@example.com"
}
```

**200 OK** – Response contains `publicKey` challenge data. Feed this into `navigator.credentials.get`.

#### Get registration options

Authenticated request (cookie or token):

```jsonc
POST /api/auth/webauthn/options
{
  "type": "register"
}
```

Use the response with `navigator.credentials.create` and then submit the returned credential to `/auth/webauthn/register`:

```jsonc
POST /api/auth/webauthn/register
{
  "id": "<credential id>",
  "rawId": "<rawId>",
  "type": "public-key",
  "name": "MacBook Pro TouchID",        // optional label
  "response": {
    "clientDataJSON": "<base64url>",
    "attestationObject": "<base64url>"
  },
  "publicKey": "<base64url public key>",
  "signCount": 1,
  "transports": ["internal"]
}
```

**201 Created** – Returns the saved credential identifier and label.

#### Verify login assertion

```jsonc
POST /api/auth/webauthn/verify
{
  "id": "<credential id>",
  "type": "public-key",
  "response": {
    "clientDataJSON": "<base64url>",
    "authenticatorData": "<base64url>",
    "signature": "<base64url>"
  },
  "signCount": 2
}
```

**200 OK**

```json
{
  "token": "1|webauthn...",
  "user": {
    "id": 5,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  }
}
```

Use the returned token for Bearer authentication or rely on the established session cookie.

### JWKS Endpoint

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/auth/.well-known/jwks.json` | None | Serves the configured RSA keys for JWT signature verification. |

Configure keys in `config/jwks.php` or via environment variables (`JWKS_PUBLIC_KEY`, etc.). The endpoint returns:

```json
{
  "keys": [
    {
      "kty": "RSA",
      "use": "sig",
      "alg": "RS256",
      "kid": "example",
      "n": "...",
      "e": "AQAB"
    }
  ]
}
```

### Secure Section Helpers

These helper endpoints are used to hydrate protected dashboard sections and require authentication (cookie or Bearer token).

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/secure/dashboard` | Returns the authenticated user plus meta `{ section: "dashboard" }`. |
| `GET` | `/secure/users` | Same structure with `{ section: "users" }`. |
| `GET` | `/secure/profile` | Same structure with `{ section: "profile" }`. |
| `GET` | `/secure/logs` | Same structure with `{ section: "logs" }`. |
| `GET` | `/secure/errors` | Same structure with `{ section: "errors" }`. |

### Admin CRUD APIs

All admin routes are protected by Sanctum and expect the authenticated user to have the appropriate permissions. Each endpoint is a standard Laravel API resource controller returning JSON arrays/objects. The payloads include related models for convenience.

| Resource | Base Path | Supported Methods |
|----------|-----------|-------------------|
| Roles | `/admin/roles` | `GET` (index/show), `POST`, `PUT/PATCH`, `DELETE` |
| Permissions | `/admin/permissions` | `GET`, `POST`, `PUT/PATCH`, `DELETE` |
| Users | `/admin/users` | `GET`, `POST`, `PUT/PATCH`, `DELETE` |
| Profiles | `/admin/profiles` | `GET`, `POST`, `PUT/PATCH`, `DELETE` |
| Teams | `/admin/teams` | `GET`, `POST`, `PUT/PATCH`, `DELETE` |
| Settings | `/admin/settings` | `GET`, `POST`, `PUT/PATCH`, `DELETE` |

#### Example: Create a user

```jsonc
POST /api/admin/users
Authorization: Bearer <token>
{
  "name": "Grace Hopper",
  "email": "grace@example.com",
  "password": "secret123",
  "roles": [1, 2],
  "teams": [
    { "id": 3, "role": "owner" }
  ],
  "profile": {
    "first_name": "Grace",
    "last_name": "Hopper",
    "phone": "+1-555-0100",
    "meta": { "title": "Admiral" }
  }
}
```

**201 Created** – Returns the created user with eager loaded relations (`roles`, `teams`, `profile`). Update and delete endpoints behave as expected for REST resources.

---

## CORS & Headers

Sanctum expects the SPA domain to be listed in `SANCTUM_STATEFUL_DOMAINS`. For cross-site requests, configure the Laravel CORS middleware (`config/cors.php`) as needed. Always send `X-XSRF-TOKEN` header when using cookie auth (Laravel automatically sets the cookie when `/sanctum/csrf-cookie` is requested, though this project primarily uses token auth from the SPA).

---

## Rate Limiting Summary

- `/auth/register`: limited via `throttle:register` (default 10/minute).
- `/auth/login` and `/auth/session/login`: limited via `throttle:login` (default 5/minute per email+IP).
- `/auth/tokens`: limited via `throttle:login` to protect token issuance.
- `/auth/magic/request`: custom throttle (5 attempts before temporary lockout).

Handle 429 responses by prompting the user to retry later.

---

## Glossary

- **Sanctum Token**: A personal access token managed by Laravel Sanctum. Send as `Authorization: Bearer <token>`.
- **Session Guard (`web`)**: Cookie-based guard used for first-party sessions. Requires `credentials: 'include'` on fetch requests.
- **Provider**: OAuth provider string (`github`, `google`) configured via `SOCIALITE_PROVIDERS`.
- **WebAuthn Credential**: Passkey registered via the WebAuthn APIs and stored in `webauthn_credentials`.

---

## Support

If the frontend encounters unexpected responses, capture the request/response payloads and share them with the backend team. Include the environment, HTTP status code, and relevant headers to accelerate troubleshooting.
