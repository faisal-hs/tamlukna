# Authorization

Package: `spatie/laravel-permission` (ADR 0001, decision 1). Business rules: ADR 0002.

## Principles
- Code checks **permissions**, never role names. Roles are named bundles of permissions.
- Roles and permissions are defined in code (`App\Enums\Role`, `App\Enums\Permission`)
  and synced to the database by `RolesAndPermissionsSeeder` (idempotent).
- Client-supplied role names or user IDs are never trusted for access decisions.
- Ownership is checked by policies in addition to permissions (from Step 8):
  e.g. editing a listing requires `properties.manage-own` **and** owning it,
  or `properties.manage-any`.
- Admin receives every permission explicitly — there is no `Gate::before` bypass.

## Role → permission matrix

| Permission | buyer | renter | owner | broker | developer | staff | admin |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `properties.create` | | | ✓ | ✓ | ✓ | | ✓ |
| `properties.manage-own` | | | ✓ | ✓ | ✓ | | ✓ |
| `properties.view-unpublished` | | | | | | ✓ | ✓ |
| `properties.moderate` | | | | | | ✓ | ✓ |
| `properties.manage-any` | | | | | | | ✓ |
| `users.view-any` | | | | | | | ✓ |
| `users.manage` | | | | | | | ✓ |
| `roles.manage` | | | | | | | ✓ |
| `audit-logs.view` | | | | | | | ✓ |

Buyers and renters need no special permissions: their own favorites, saved searches
and inquiries (Phase 3) are protected by ownership rules, not permissions.

## How roles are assigned
- **Registration:** optional `account_type` = `buyer` (default), `renter` or `owner`.
  Anything else is rejected with `422`.
- **Broker / developer:** require license verification — assigned by an administrator.
- **Staff / admin:** never self-assigned.
- **Phase 1 tool (server only):**
  `docker compose exec app php artisan users:assign-role <email> <role> [--revoke]`.
  Role-management APIs are Phase 2 (dashboard).

## Enforcing in routes and code
- Route middleware: `permission:properties.moderate` (403 `forbidden` if missing).
- Code: `$user->can(Permission::PropertiesModerate->value)` or a Policy.
- Permissions are resolved against the User model's `web` guard; API requests
  authenticate with Sanctum and still use these permissions.

## Changing roles or permissions
1. Edit `App\Enums\Permission` / `Role::permissions()`.
2. Update this matrix and the tests.
3. Deploy, then run
   `php artisan db:seed --class=RolesAndPermissionsSeeder --force`.
   (Removed permissions are not deleted automatically; remove them deliberately.)

`/me` returns `roles` and `permissions` so clients can adapt their UI; the server
enforces every check regardless.
