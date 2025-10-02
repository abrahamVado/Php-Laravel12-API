# Next.js Integration Setup Checklist

This guide summarizes the environment, middleware, and headers you need to configure when wiring a Next.js frontend to the Yamato Laravel API. Use it alongside the endpoint-specific guides.

## 1. API host and health probe
- **Base URL**: configure your Next.js API client with the Laravel base URL (e.g., `http://localhost:8000`).
- **Health check**: `GET /api/health` returns `{ "ok": true }`, which you can use for readiness checks before enabling protected flows.【F:routes/api.php†L20-L22】

## 2. Sanctum authentication modes
Laravel Sanctum supports both bearer tokens and same-site cookies in this project.【F:routes/api.php†L56-L88】【F:routes/api.php†L95-L100】

| Mode | Use case | Requirements |
| --- | --- | --- |
| **Bearer tokens** | Server-side rendering (SSR) calls from Next.js, or static site requests. | Issue tokens via `POST /api/auth/tokens` (see the authentication guide) and send `Authorization: Bearer <token>` on protected calls. |
| **Cookie-based session** | Browser requests from the Next.js app when hosted on the same domain family. | Include your front-end domain(s) in `SANCTUM_STATEFUL_DOMAINS`, enable cross-site cookies when needed, and call the session login endpoint. |

## 3. Environment variables to review
Configure these Laravel variables so that cookies and OAuth flows align with the domains used by Next.js.

| Variable | Purpose | Default / location |
| --- | --- | --- |
| `SANCTUM_STATEFUL_DOMAINS` | Comma-separated list of hosts that should receive Sanctum cookies. Must include every Next.js origin that uses cookie auth. | Defaults to local hosts plus the current app URL.【F:config/sanctum.php†L14-L23】 |
| `SESSION_DRIVER` | Storage backend for cookie sessions. Default `database` ensures stateless frontends can share sessions across instances. | See `config/session.php` for default and alternatives.【F:config/session.php†L12-L25】 |
| `SESSION_DOMAIN` | Root domain that issues cookies. Set it to your apex domain (e.g., `.example.com`) when sharing cookies between subdomains. | Controlled via `config/session.php`.【F:config/session.php†L62-L86】 |
| `SESSION_SECURE_COOKIE` | Force HTTPS-only cookies in production. Enable (`true`) once your site is fully HTTPS. | Configured in `config/session.php`.【F:config/session.php†L88-L107】 |
| `SESSION_SAME_SITE` | Adjust to `none` when the API and Next.js run on different domains and you need cross-site cookies. | See `config/session.php` comments for options.【F:config/session.php†L108-L124】 |
| `APP_URL` | Used by Sanctum when computing first-party URLs. Set to the fully-qualified backend URL. | Referenced in `config/sanctum.php`.【F:config/sanctum.php†L14-L23】 |

## 4. Middleware expectations
- API endpoints that rely on session cookies require the `web` middleware in addition to Sanctum. Laravel already applies it on routes such as `/api/auth/session/*`; ensure your Next.js client sends the `X-XSRF-TOKEN` header extracted from the `XSRF-TOKEN` cookie when making POST/DELETE requests with cookies.
- Bearer-protected endpoints are grouped under the `auth:sanctum` middleware. Confirm that your Next.js fetch helpers attach the bearer token when calling these routes.【F:routes/api.php†L56-L94】

## 5. Database migrations for sessions
Because the session driver defaults to `database`, run `php artisan migrate` so the `sessions` table exists before testing cookie logins.

## 6. Cross-origin considerations
- Laravel does not ship with an explicit CORS configuration file in this project. If you expose the API to browsers on a different origin, add the `Fruitcake\\Cors` middleware and define allowed origins via the `CORS_ALLOWED_ORIGINS` (custom) or by publishing `config/cors.php`.
- When using `fetch` from Next.js on the client, remember to set `credentials: 'include'` for cookie-based auth.

## 7. Suggested Next.js helpers
- Create a shared API client that reads the base URL from an environment variable like `process.env.NEXT_PUBLIC_API_URL`.
- Provide wrappers for `getServerSideProps`/`getStaticProps` that can inject bearer tokens from Next.js API routes when calling the Laravel backend.
