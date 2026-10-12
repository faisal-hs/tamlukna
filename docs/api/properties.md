# Property API (v1)

Conventions: [conventions.md](conventions.md) · Lifecycle and rules: [listing-lifecycle](../architecture/listing-lifecycle.md)
`{reference}` is the public listing reference `TML-XXXXXXXX`.

| Method | Path | Auth | Success |
|---|---|---|---|
| GET | `/properties/{reference}` | optional bearer | 200 |
| POST | `/properties` | bearer + `properties.create` | 201 |
| PATCH | `/properties/{reference}` | bearer, manager | 200 |
| DELETE | `/properties/{reference}` | bearer, manager | 200 |
| POST | `/properties/{reference}/submit` · `/pause` · `/resume` | bearer, manager | 200 |
| GET | `/properties/{reference}/history` | bearer, manager or staff | 200 |
| GET | `/me/properties` | bearer | 200 (paginated) |

"Manager" = the listing's advertiser with `properties.manage-own`, or `properties.manage-any`.

## Create / update body
| Field | Create | Notes |
|---|---|---|
| `property_type_id` | required | active type |
| `purpose` | required | `sale` \| `rent` |
| `rent_period` | required for rent | `yearly` \| `monthly`; rejected for sale |
| `title_ar` | required | 5–150 chars; `title_en` optional |
| `description_ar`, `description_en` | optional | ≤ 5000; Arabic description (≥ 30) required to **submit** |
| `city_id`, `district_id` | required | district must belong to the city; on update, changing `city_id` requires `district_id` |
| `latitude`, `longitude` | required | inside Saudi Arabia |
| `address_line` | optional | private (advertiser only) |
| `area_sqm` | required | > 0 |
| `price` | required | ≥ 0 (> 0 to submit), SAR |
| `bedrooms`, `bathrooms`, `living_rooms` | optional | rejected for types without rooms; bedrooms + bathrooms required to submit if the type has rooms |
| `floor_number`, `total_floors`, `building_age_years`, `furnishing`, `property_condition` | optional | rejected for types without building details |
| `street_width_m`, `facade`, `ad_license_number` | optional | licence number stored as provided, not verified |
| `features` | optional | array of feature IDs (max 30); `[]` clears; omit to leave unchanged |

Lifecycle fields (`status`, `advertiser_id`, `reference`, dates, verification) are ignored.
PATCH accepts any subset of fields.

## Response (`data`)
`reference`, `status`, `purpose`, `rent_period`, `property_type{slug,name_ar,name_en,category}`,
`title{ar,en}`, `description{ar,en}`, `location{city, district, latitude, longitude}`,
`area_sqm`, `street_width_m`, `facade`, `rooms{…}`, `building{…}`,
`price{amount, currency, per_sqm}` (decimal **strings**), `features[]`, `ad_license_number`,
`verification_status`, `advertiser{id, name}`, `published_at`, `expires_at`.
Managers additionally get `location.address_line`, `submitted_at`, `moderated_at`,
`created_at`, `updated_at`. Advertiser email/phone are never included.

## `GET /me/properties`
Query: `status` (any listing status), `sort` (`created_at`, `-created_at` (default),
`updated_at`, `-updated_at`, `price`, `-price`), `page`, `per_page` (≤ 50).

## History entry
`{from, to, reason, actor: "advertiser" | "moderator" | "system", created_at}` — staff identities are not exposed.

## Errors
| HTTP | code | When |
|---|---|---|
| 401 | unauthenticated | missing/invalid token on protected endpoints |
| 403 | forbidden | visible listing, action not allowed; or no create permission |
| 403 | email_not_verified | submit without a verified email |
| 403 | account_inactive | suspended/deactivated account |
| 404 | not_found | unknown reference, deleted, or not visible to the caller |
| 409 | invalid_status_transition | action not allowed in the current status (incl. resume after expiry) |
| 409 | listing_not_editable | edit while under review |
| 422 | validation_failed | field errors (also completeness errors on submit) |

Not yet: public search/filtering (Step 10), images (Step 9), approve/reject (Step 11 CLI; Phase 2 API).
