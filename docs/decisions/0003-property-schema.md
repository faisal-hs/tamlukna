# ADR 0003 — Property schema

- **Status:** Accepted (open questions resolved by the owner on 2026-10-12)
- **Scope:** Step 7.2. Validated on MySQL 8.0.46 (sandbox) and by the test suite on MySQL 8.4.

| # | Decision | Rationale |
|---|---|---|
| 1 | `latitude`/`longitude` `DECIMAL(10,7)` are the source of truth; `location` is a **STORED generated** `POINT SRID 4326` with a `SPATIAL INDEX` | Writes stay plain decimals (no binary geometry handling in PHP); the database keeps the point consistent. Added with raw SQL because Laravel's grammar emits invalid SQL for generated + SRID columns. |
| 2 | Radius search = bounding-box `MBRContains` (uses the spatial index) + exact `ST_Distance_Sphere` (Step 10) | `ST_Distance_Sphere` alone cannot use an index; the box pre-filters. Verified with `EXPLAIN`. |
| 3 | `price_per_sqm` is a stored generated column | Always consistent; indexable for sorting. |
| 4 | Public reference `TML-` + 8 Crockford base-32 chars, unique even for soft-deleted rows | Non-sequential; unambiguous to read aloud or type. |
| 5 | `advertiser_id` = account responsible for the listing (owner/broker/developer); `RESTRICT` on delete | Listings must never disappear because an account is removed. |
| 6 | Properties use soft deletes; status changes go to append-only `property_status_histories`; important actions also to `audit_logs` | Moderation history and accountability. |
| 7 | One cover per property via a unique generated column `cover_property_id` | Enforced by the database, not just code. Consequence: `property_media.property_id` must be `RESTRICT` (MySQL forbids cascades on base columns of generated columns) — also correct because files must be deleted by the application first. |
| 8 | Type-dependent attributes are nullable; `property_types.allows_rooms` / `allows_building_details` decide which apply | Land/commercial listings are not forced to carry bedrooms or floors. |
| 9 | `ad_license_number` is stored as provided; `verification_status` defaults to `unverified` | No official verification is claimed without an authorized source. |
| 10 | Original upload file names are not stored | They can contain personal data and are not needed. |
| 11 | `purpose` is `sale` or `rent` (+ `rent_period`) | See open question 1. |

## Resolved questions (owner decision, 2026-10-12)
1. **Investment listings:** out of scope for now.
2. **District:** required, and it must belong to the listing's city (composite foreign key, Step 8.1).
3. **Advertising licence number:** optional, stored when provided; not verified.
