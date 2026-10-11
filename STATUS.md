# Tamlukna — Project Status

**Current phase:** Phase 1 — Backend Foundation (in progress)
**Phase 2 / Phase 3:** Not started — require explicit approval.

## Completed
- [x] Step 1 — Environment and repository inspection (greenfield, no existing work)
- [x] Step 2 — Git repository initialized; Laravel 12 skeleton installed in `backend/`

## Planned (Phase 1)
- [x] Step 3 — Docker Compose: PHP 8.4-FPM, nginx, MySQL 8, MySQL test DB, Redis
- [x] Step 4 — API foundation: /api/v1, response envelope, exceptions, pagination, health, CORS, logging
- [ ] Step 5 — Authentication (Sanctum) and account status
- [ ] Step 6 — Roles and permissions
- [ ] Step 7 — Database schema and documentation
- [ ] Step 8 — Property domain and state machine
- [ ] Step 9 — Media storage and validation
- [ ] Step 10 — Search, filters, spatial radius queries
- [ ] Step 11 — Minimal moderation command (testing only)
- [ ] Step 12 — OpenAPI, full test run, README, phase-gate report

## Environment notes
- Host PHP 8.4.26; Docker containers will also use PHP 8.4.
- Host `mysql` client is MariaDB 11.8 — NOT used for development or tests. MySQL 8 runs in Docker.
- Host PHP lacks the `redis` extension; the Docker image provides it.

## Decisions pending owner approval
- Decisions 1–10 from the initial plan (permissions package, OpenAPI tool, Larastan,
  PHPUnit, auth strategy, bilingual columns, spatial design, money type, public reference
  format, city/district data source).
- Arabic field-level validation messages: write `lang/ar/validation.php` ourselves vs. a package such as `laravel-lang/lang`.

## Decisions made
- Laravel 12 retained (owner decision, 2026-10-11). Laravel 12 security fixes end ~Feb 2027 — plan an upgrade before then.
- Default API locale: Arabic (`ar`); English via `Accept-Language: en`; missing translations fall back to `en`.
