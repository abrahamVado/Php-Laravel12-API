# Protected & Admin API Reference

Use this guide when wiring authenticated dashboards or admin screens from Next.js. All endpoints listed here require a valid Sanctum token or session cookie because they are wrapped in the `auth:sanctum` middleware group.【F:routes/api.php†L56-L94】

## 1. Secure page data
These endpoints power authenticated dashboard sections and return the current user plus section metadata.

| Method & Path | Description | Sample meta payload |
| --- | --- | --- |
| `GET /api/secure/dashboard` | Dashboard summary gate. | `{ "section": "dashboard", "message": "Authenticated access granted." }`【F:routes/api.php†L72-L78】【F:app/Http/Controllers/Secure/SecurePageController.php†L12-L36】 |
| `GET /api/secure/users` | Users area guard for client-side routing. | Meta section `"users"`.【F:routes/api.php†L79-L81】【F:app/Http/Controllers/Secure/SecurePageController.php†L16-L36】 |
| `GET /api/secure/profile` | Profile management entry point. | Meta section `"profile"`.【F:routes/api.php†L82-L84】【F:app/Http/Controllers/Secure/SecurePageController.php†L18-L36】 |
| `GET /api/secure/logs` | Ops/logs section placeholder. | Meta section `"logs"`.【F:routes/api.php†L85-L87】【F:app/Http/Controllers/Secure/SecurePageController.php†L20-L36】 |
| `GET /api/secure/errors` | Error reporting section placeholder. | Meta section `"errors"`.【F:routes/api.php†L88-L90】【F:app/Http/Controllers/Secure/SecurePageController.php†L22-L36】 |

### Front-end usage tips
- Use these endpoints to confirm auth state during client-side navigation in Next.js (e.g., `useEffect` guard that fetches `/api/secure/dashboard`).
- Because responses embed the authenticated user resource, you can hydrate global stores with profile data in one request.

## 2. Admin resources
All admin resources expose full RESTful endpoints via `Route::apiResource`. The tables below summarize validation rules so you can build the correct forms.

### 2.1 Users
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/users` | Returns users with roles, teams, and profile eager-loaded.【F:routes/api.php†L91-L100】【F:app/Http/Controllers/Admin/UserController.php†L16-L23】 |
| Create | `POST /api/admin/users` | Requires `name`, `email`, `password`. Accepts `roles` array, `teams` array of `{ id, role }`, and optional `profile` object.【F:app/Http/Controllers/Admin/UserController.php†L25-L73】 |
| Show | `GET /api/admin/users/{id}` | Returns the user with relations.【F:app/Http/Controllers/Admin/UserController.php†L35-L39】 |
| Update | `PUT/PATCH /api/admin/users/{id}` | Same payload as create but fields optional; password updates only when provided.【F:app/Http/Controllers/Admin/UserController.php†L41-L67】 |
| Delete | `DELETE /api/admin/users/{id}` | Responds with `204` on success.【F:app/Http/Controllers/Admin/UserController.php†L69-L75】 |

### 2.2 Roles
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/roles` | Returns roles with permissions eager-loaded.【F:app/Http/Controllers/Admin/RoleController.php†L17-L23】 |
| Create | `POST /api/admin/roles` | Requires unique `name`; optional `display_name`, `description`, and `permissions` array of IDs.【F:app/Http/Controllers/Admin/RoleController.php†L25-L47】 |
| Show | `GET /api/admin/roles/{id}` | Includes permissions relationship.【F:app/Http/Controllers/Admin/RoleController.php†L49-L53】 |
| Update | `PUT/PATCH /api/admin/roles/{id}` | Same schema as create; syncing permissions when provided.【F:app/Http/Controllers/Admin/RoleController.php†L55-L76】 |
| Delete | `DELETE /api/admin/roles/{id}` | Returns `204` after deletion.【F:app/Http/Controllers/Admin/RoleController.php†L78-L84】 |

### 2.3 Permissions
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/permissions` | Returns ordered collection of permissions.【F:app/Http/Controllers/Admin/PermissionController.php†L17-L23】 |
| Create | `POST /api/admin/permissions` | Requires unique `name`; optional `display_name`, `description`.【F:app/Http/Controllers/Admin/PermissionController.php†L25-L41】 |
| Show | `GET /api/admin/permissions/{id}` | Returns the single permission object.【F:app/Http/Controllers/Admin/PermissionController.php†L43-L47】 |
| Update | `PUT/PATCH /api/admin/permissions/{id}` | Optional fields with uniqueness checks on `name`.【F:app/Http/Controllers/Admin/PermissionController.php†L49-L63】 |
| Delete | `DELETE /api/admin/permissions/{id}` | Returns `204` when removed.【F:app/Http/Controllers/Admin/PermissionController.php†L65-L71】 |

### 2.4 Profiles
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/profiles` | Returns profiles with user relation eager-loaded.【F:app/Http/Controllers/Admin/ProfileController.php†L17-L23】 |
| Create | `POST /api/admin/profiles` | Requires unique `user_id` plus optional name/phone/meta fields.【F:app/Http/Controllers/Admin/ProfileController.php†L25-L43】 |
| Show | `GET /api/admin/profiles/{id}` | Returns the profile with user relation.【F:app/Http/Controllers/Admin/ProfileController.php†L35-L39】 |
| Update | `PUT/PATCH /api/admin/profiles/{id}` | Same payload as create but fields optional; `user_id` remains unique.【F:app/Http/Controllers/Admin/ProfileController.php†L41-L63】 |
| Delete | `DELETE /api/admin/profiles/{id}` | Returns `204` when deleted.【F:app/Http/Controllers/Admin/ProfileController.php†L65-L71】 |

### 2.5 Teams
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/teams` | Returns teams with member relationships.【F:app/Http/Controllers/Admin/TeamController.php†L17-L23】 |
| Create | `POST /api/admin/teams` | Requires unique `name`; accepts `description` and `members` array of `{ id, role }`.【F:app/Http/Controllers/Admin/TeamController.php†L25-L61】 |
| Show | `GET /api/admin/teams/{id}` | Returns the team with members.【F:app/Http/Controllers/Admin/TeamController.php†L33-L37】 |
| Update | `PUT/PATCH /api/admin/teams/{id}` | Same schema as create; updates relationships via sync.【F:app/Http/Controllers/Admin/TeamController.php†L39-L73】 |
| Delete | `DELETE /api/admin/teams/{id}` | Returns `204` once removed.【F:app/Http/Controllers/Admin/TeamController.php†L75-L81】 |

### 2.6 Settings
| Operation | Path | Notes |
| --- | --- | --- |
| List | `GET /api/admin/settings` | Returns all settings ordered by key.【F:app/Http/Controllers/Admin/SettingController.php†L17-L23】 |
| Create | `POST /api/admin/settings` | Requires unique `key`; accepts `value` and `type`.【F:app/Http/Controllers/Admin/SettingController.php†L25-L45】 |
| Show | `GET /api/admin/settings/{id}` | Returns the setting object.【F:app/Http/Controllers/Admin/SettingController.php†L47-L51】 |
| Update | `PUT/PATCH /api/admin/settings/{id}` | Same payload as create; validates uniqueness of `key`.【F:app/Http/Controllers/Admin/SettingController.php†L53-L71】 |
| Delete | `DELETE /api/admin/settings/{id}` | Returns `204` when deleted.【F:app/Http/Controllers/Admin/SettingController.php†L73-L79】 |

## 3. Front-end workflow suggestions
1. //1.- Fetch the relevant list endpoint (e.g., `/api/admin/users`) on page load or via SWR/React Query to populate tables.
2. //2.- Use the validation notes to build client-side schemas (e.g., Zod) so you can mirror backend rules and show inline errors.
3. //3.- Submit mutations to the REST endpoints above and optimistically update the client cache when a `2xx` response is returned.
4. //4.- Handle `401/419` responses by redirecting to your Next.js login flow and clearing stored tokens.
