# Authentication Endpoints

This document covers every endpoint under `/api/auth/*` so you can wire up registration, login, and token management flows in Next.js.

## 1. Public registration and login
| Method & Path | Middleware | Description | Response |
| --- | --- | --- | --- |
| `POST /api/auth/register` | `throttle:register` | Creates a new user via name, email, password payload. | Returns the created user resource and `201` on success.【F:routes/api.php†L25-L33】【F:app/Http/Controllers/Auth/RegisterController.php†L14-L31】 |
| `POST /api/auth/login` | `throttle:login`, `web` | Email/password login that issues a session cookie. | Returns the authenticated user resource with a success meta message.【F:routes/api.php†L34-L41】【F:app/Http/Controllers/Auth/LoginController.php†L16-L63】 |

### Required payload structure
```json
{
  "name": "Jane Doe", // registration only
  "email": "user@example.com",
  "password": "secret-password",
  "remember": true // optional flag for session login
}
```

## 2. SPA session endpoints
| Method & Path | Middleware | Purpose |
| --- | --- | --- |
| `POST /api/auth/session/login` | `throttle:login`, `web` | Issues a same-site session cookie for SPA requests.【F:routes/api.php†L37-L41】【F:app/Http/Controllers/Auth/SessionController.php†L15-L49】 |
| `POST /api/auth/session/logout` | `web` | Logs out the current session and clears cookies.【F:routes/api.php†L42-L45】【F:app/Http/Controllers/Auth/SessionController.php†L51-L63】 |
| `GET /api/auth/me` | `auth:sanctum` | Returns the authenticated user when a cookie or token is present.【F:routes/api.php†L58-L60】【F:app/Http/Controllers/Auth/SessionController.php†L65-L69】 |

> **Next.js tip:** When using these endpoints from the browser, ensure requests include `credentials: 'include'` and the `X-XSRF-TOKEN` header.

## 3. Personal access tokens
| Method & Path | Middleware | Description |
| --- | --- | --- |
| `POST /api/auth/tokens` | `throttle:login` | Issues a new Sanctum token using email/password credentials. Pass `device_name` to label the token.【F:routes/api.php†L95-L100】【F:app/Http/Controllers/Auth/TokenController.php†L17-L52】 |
| `GET /api/auth/tokens` | `auth:sanctum` | Lists the current user's tokens (id, name, timestamps).【F:routes/api.php†L64-L67】【F:app/Http/Controllers/Auth/TokenController.php†L54-L58】 |
| `DELETE /api/auth/tokens/{id}` | `auth:sanctum` | Revokes a specific token by ID. Returns `404` if not found.【F:routes/api.php†L68-L71】【F:app/Http/Controllers/Auth/TokenController.php†L60-L71】 |

### Token issuance payload
```json
{
  "email": "user@example.com",
  "password": "secret-password",
  "device_name": "nextjs-ssr" // optional, defaults to user agent
}
```

## 4. Passwordless options
| Endpoint | Middleware | Notes |
| --- | --- | --- |
| `POST /api/auth/magic/request` | `web` | Stub endpoint to request a magic-link email. Customize the controller before production use.【F:routes/api.php†L44-L48】 |
| `POST /api/auth/magic/verify` | `web` | Stub endpoint to verify magic links. Pair with your own token issuance logic.【F:routes/api.php†L49-L52】 |

## 5. OAuth helpers
| Endpoint | Purpose |
| --- | --- |
| `GET /api/auth/oauth/redirect/{provider}` | Redirects the browser to the external OAuth provider (e.g., GitHub, Google).【F:routes/api.php†L53-L55】 |
| `GET /api/auth/oauth/callback/{provider}` | Handles the provider callback. Extend the controller to create local users or tokens.【F:routes/api.php†L53-L55】 |

## 6. WebAuthn support
| Endpoint | Middleware | Description |
| --- | --- | --- |
| `POST /api/auth/webauthn/options` | none | Returns registration or authentication options for WebAuthn. Use this before invoking browser APIs.【F:routes/api.php†L56-L58】 |
| `POST /api/auth/webauthn/register` | `auth:sanctum` | Registers a WebAuthn credential for the current user.【F:routes/api.php†L59-L61】 |
| `POST /api/auth/webauthn/verify` | none | Verifies a WebAuthn assertion and should result in a login/session once implemented.【F:routes/api.php†L62-L63】 |

## 7. JWKS discovery
- `GET /api/auth/.well-known/jwks.json` responds with the JSON Web Key Set the API is configured to expose (empty by default). Use this when integrating OAuth/OIDC clients that expect JWKS metadata.【F:routes/api.php†L64-L66】

## 8. Error handling expectations
- Invalid credentials raise `422` validation errors for both session and token flows, so handle them with form-level validation states in Next.js.【F:app/Http/Controllers/Auth/LoginController.php†L35-L49】【F:app/Http/Controllers/Auth/TokenController.php†L24-L41】
- Unverified emails trigger `403` responses prompting verification. Surface this status to users so they can resend verification emails.【F:app/Http/Controllers/Auth/LoginController.php†L50-L63】【F:app/Http/Controllers/Auth/SessionController.php†L33-L47】【F:app/Http/Controllers/Auth/TokenController.php†L30-L43】
