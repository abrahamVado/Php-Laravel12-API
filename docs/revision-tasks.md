# Identified Improvements

## Task 1: Repair the Docker quick-start command in the README
- **Problem:** The installation command in the README splits `php artisan storage:link` across two lines, which breaks copy/paste execution in the shell.
- **Proposed Actions:** Update the README so the command appears on a single line and verify the rendered Markdown preserves it.
- **Acceptance Criteria:** README shows the full command on a single line within the fenced block and copying it executes without syntax errors.

## Task 2: Implement first-party OAuth login via Socialite
- **Problem:** The OAuth controller currently returns HTTP 501 for both the redirect and callback endpoints, leaving social login unsupported.
- **Proposed Actions:** Install and configure Laravel Socialite (or equivalent), implement the redirect and callback handlers, and cover the flow with feature tests.
- **Acceptance Criteria:** `/api/auth/oauth/redirect/{provider}` initiates the OAuth flow, `/api/auth/oauth/callback/{provider}` signs users in or creates accounts as configured, and automated tests cover success and failure scenarios.

## Task 3: Deliver WebAuthn registration and authentication flows
- **Problem:** The WebAuthn controller stubs respond with HTTP 501, so passkey-based authentication is unavailable.
- **Proposed Actions:** Integrate a WebAuthn library, implement the options, register, and verify endpoints, and persist credentials in the existing `webauthn_credentials` table.
- **Acceptance Criteria:** The endpoints issue proper WebAuthn challenge options, register credentials, verify assertions, and have feature tests validating the flows.

## Task 4: Provide JWKS key management configuration
- **Problem:** The JWKS controller reads from `config('jwks.keys')`, but no configuration file is provided, making the endpoint return an empty key set by default.
- **Proposed Actions:** Add a dedicated `config/jwks.php` file (with environment-driven defaults) and document how to manage signing keys.
- **Acceptance Criteria:** JWKS configuration exists with sample entries, documentation explains usage, and the endpoint returns configured keys in tests.
