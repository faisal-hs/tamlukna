# ADR 0001 — Phase 1 foundation decisions

- **Status:** Accepted (owner approval, 2026-10-11)
- **Scope:** Phase 1 backend foundation

| # | Decision | Rationale |
|---|---|---|
| — | Laravel 12 retained (not 13) | Owner decision. Laravel 12 security fixes end ~Feb 2027; schedule an upgrade before then. |
| — | PHP 8.4 (Docker), MySQL 8.4 LTS, Redis 7.4, nginx | Matches host PHP; MySQL 8 LTS line as required by the brief. Host MariaDB is never used. |
| — | Default API locale `ar`, English via `Accept-Language: en`, fallback `en` | Saudi-first product; bilingual from day one. |
| — | Arabic validation messages maintained in-repo | No translation package; guard tests enforce key and placeholder parity. |
| 1 | `spatie/laravel-permission` for roles/permissions | Mature, widely used, integrates with Gates/Policies. |
| 2 | `dedoc/scramble` for OpenAPI | Generated from Form Requests and Resources, so docs cannot drift from code. |
| 3 | Larastan (PHPStan) static analysis, level 6 | Catches type and Eloquent mistakes early. |
| 4 | PHPUnit (Laravel 12 default) | No extra test framework dependency. Tests run on MySQL, not SQLite, because of spatial features. |
| 5 | Sanctum bearer tokens (expiring, per-device, revocable) now; Sanctum SPA cookie mode for first-party web clients in a later approved phase | One auth guard serves all clients; no duplicate implementations. |
| 6 | Fixed `_ar` / `_en` columns for translatable text | Exactly two languages; simpler and faster than a translations table. |
| 7 | `POINT SRID 4326 NOT NULL` + `SPATIAL INDEX`, radius via `ST_Distance_Sphere` | Native MySQL 8 spatial support; SRID 4326 axis order (latitude first) verified in Step 3. |
| 8 | Money as `DECIMAL(14,2)` with explicit currency (`SAR`) | Exact arithmetic; no floats for money. |
| 9 | Public listing reference `TML-XXXXXXXX`, non-sequential, unique | Stable public identifier that does not reveal listing volume. |
| 10 | Seed only a small set of major cities with clearly labelled sample districts | No fabricated data; a licensed district dataset must be chosen before production. |

## Consequences
- New Composer dependencies are limited to items 1–3 plus Sanctum (fixed stack).
- A licensed city/district data source remains an open item.
