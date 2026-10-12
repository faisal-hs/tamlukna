# Tamlukna — Project Status

**Current phase:** Phase 1 — Backend Foundation (in progress)
**Phase 2 / Phase 3:** Not started — require explicit approval.

## Phase 1 progress
- [x] Step 1 — Environment and repository inspection
- [x] Step 2 — Git repository and Laravel 12 skeleton
- [x] Step 3 — Docker Compose: PHP 8.4-FPM, nginx, MySQL 8.4 (+ test DB), Redis
- [x] Step 4 — API foundation: `/api/v1`, envelope, error handling, Arabic/English (Arabic default, incl. validation), request IDs, rate limiting, CORS, health check
- [x] Step 4.4 — API-only cleanup (Blade/Vite/Sail removed), Larastan level 6, README run guide, decision record
- [x] Step 5.1 — Sanctum token auth: register, login (throttled), logout, logout-all, /me, account status
- [x] Step 5.2 — Password reset (revokes all tokens) and signed email verification, localized emails
- [x] Step 6 — Roles and permissions (spatie): enum-defined roles/permissions, idempotent seeder, default role at registration, users:assign-role CLI
- [x] Step 7.1 — Locations (13 regions, sample cities/districts), catalog (types/features), immutable audit log, morph aliases
- [ ] Step 7.2 — Properties (spatial), media, status history, factories, local sample listings, schema docs
- [x] Step 8.1 — Listing lifecycle (named transitions, row locks, history + audit), completeness rules, PropertyPolicy, PropertyService, hourly expiry, district required (composite FK)
- [x] Step 8.2 — Property APIs: create, show (public/optional auth), update, delete, submit/pause/resume, history, my listings
- [ ] Step 9 — Media storage and validation
- [ ] Step 10 — Search, filters, spatial radius queries
- [ ] Step 11 — Minimal moderation command (testing only)
- [ ] Step 12 — OpenAPI, full verification, phase-gate report

## Decisions
All decisions to date: [docs/decisions/0001-phase-1-foundation.md](docs/decisions/0001-phase-1-foundation.md).

## Open items
- Laravel 12 security support ends ~Feb 2027 — schedule an upgrade.
- `backend/composer.json` still declares the skeleton's `"license": "MIT"` — owner to confirm the project's license.
- Laravel's signed-URL file routes for the private disk (`storage/{path}`) — keep or disable in Step 9.
- A licensed Saudi city/district dataset is needed before production data.

## Environment notes
- Host PHP 8.4.26 is not used to run the app; all PHP tooling runs in Docker.
- Host `mysql` client is MariaDB 11.8 — never used for development or tests.
- No scheduler runs yet (`schedule:run`); needed for token pruning before production.
- Emails are sent synchronously; add a queue worker and queued notifications before production.
- Default Laravel email layout is LTR; RTL email theme planned for Phase 3 notifications.
- ADR 0002 (roles and self-registration rules) is **Proposed** — awaiting owner approval.
- Role changes via CLI are logged to the application log only; persistent audit records arrive in Step 7.
- District is required: until a licensed district dataset is loaded, listings can only be created in cities that have districts (sample: Riyadh, Jeddah).
- Privacy defaults to confirm: coordinates and advertiser name are public; exact address is advertiser-only; contact details never public.
