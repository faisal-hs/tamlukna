# Tamlukna — Project Status

**Current phase:** Phase 1 — Backend Foundation (in progress)
**Phase 2 / Phase 3:** Not started — require explicit approval.

## Phase 1 progress
- [x] Step 1 — Environment and repository inspection
- [x] Step 2 — Git repository and Laravel 12 skeleton
- [x] Step 3 — Docker Compose: PHP 8.4-FPM, nginx, MySQL 8.4 (+ test DB), Redis
- [x] Step 4 — API foundation: `/api/v1`, envelope, error handling, Arabic/English (Arabic default, incl. validation), request IDs, rate limiting, CORS, health check
- [x] Step 4.4 — API-only cleanup (Blade/Vite/Sail removed), Larastan level 6, README run guide, decision record
- [ ] Step 5 — Authentication (Sanctum) and account status
- [ ] Step 6 — Roles and permissions
- [ ] Step 7 — Database schema and documentation
- [ ] Step 8 — Property domain and state machine
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
