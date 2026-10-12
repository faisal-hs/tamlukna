# Database schema

MySQL 8 (8.4 in Docker), `utf8mb4` / `utf8mb4_0900_ai_ci`, all timestamps UTC.
This document grows with each step; Step 7.2 adds properties, media and status history.

## Conventions
- Table names plural snake_case; foreign keys `<singular>_id` with explicit FK constraints.
- Translatable text: fixed `*_ar` / `*_en` columns (ADR 0001, decision 6).
- Reference rows record their origin in `data_source` (`sample`, `iso-3166-2:sa`, or a
  licensed dataset name later).
- Polymorphic columns store **aliases** (`user`, later `property`), never PHP class
  names — enforced by `Relation::enforceMorphMap()` in `AppServiceProvider`.
- Money: `DECIMAL(14,2)` + explicit currency. Coordinates: `DECIMAL(10,7)`.

## Users and access
| Table | Purpose / notes |
|---|---|
| `users` | Accounts. Internal `id` + public `public_id` (ULID). `status` (active/suspended/deactivated), `preferred_locale`, optional unique Saudi `phone`. |
| `personal_access_tokens` | Sanctum tokens (`tokenable_type = 'user'`), with `expires_at`. |
| `password_reset_tokens` | Hashed reset tokens (60 min). |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | spatie/laravel-permission. Synced from `App\Enums\Role` / `Permission`. |

## Locations (reference data)
| Table | Key columns | Constraints |
|---|---|---|
| `regions` | `code` (ISO 3166-2:SA), `name_ar`, `name_en` | `code` unique |
| `cities` | `region_id`, `slug`, names, approximate `latitude`/`longitude`, `is_active` | `slug` unique; FK → regions (restrict delete) |
| `districts` | `city_id`, `slug`, names, optional coordinates, `is_active` | `(city_id, slug)` unique; FK → cities (restrict delete) |

Seeded data: all 13 regions (factual). Cities and districts are a **sample**
(`data_source = 'sample'`) until a licensed dataset is chosen — see STATUS.md.

## Catalog (platform-managed)
| Table | Notes |
|---|---|
| `property_types` | `slug`, names, `category` (residential/commercial/agricultural), `allows_rooms`, `allows_building_details`, `sort_order`, `is_active`. The two flags decide which attributes a listing of that type may carry (Step 8 validation). |
| `features` | Amenities: `slug`, names, `group` (interior/building/exterior), `sort_order`, `is_active`. |

## Audit
| Table | Notes |
|---|---|
| `audit_logs` | Append-only (model refuses update/delete). `actor_id` (nullable; set to NULL if the user is deleted), `action` (e.g. `user.role_assigned`), polymorphic `auditable`, `changes` JSON (varies by action — the one justified JSON column), `source` (api/cli/system), `ip_address`, `user_agent`, `request_id` (matches the `X-Request-Id` response header). |

## Seeding
- `php artisan db:seed` — roles/permissions, locations, catalog. Idempotent; safe in every environment.
- Tests seed roles/permissions automatically; other reference data comes from factories
  (`data_source = 'factory'`).
