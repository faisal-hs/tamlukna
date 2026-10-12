# Listing lifecycle

Code: `App\Enums\ListingTransition`, `App\Services\Properties\ListingWorkflow`,
`App\Policies\PropertyPolicy`. Clients request **actions**; they never send a status.

| Action | From | To | Who | Notes |
|---|---|---|---|---|
| submit | draft, rejected, expired | pending_review | advertiser | Verified email required; completeness check |
| approve | pending_review | published | moderator | Sets `published_at`, `expires_at` (+`LISTING_DURATION_DAYS`, default 90), `moderated_at` |
| reject | pending_review, published, paused | rejected | moderator | Reason required (5–1000 chars), stored in history |
| pause | published | paused | advertiser | |
| resume | paused | published | advertiser | Refused if `expires_at` has passed |
| revise | published, paused | pending_review | automatic | Triggered by any content edit |
| expire | published, paused | expired | scheduler | `listings:expire`, hourly |

## Editing
- Allowed in every status **except `pending_review`** (409 `listing_not_editable`).
- Editing a **published or paused** listing sends it back to review (prevents bait-and-switch).
- Only content fields are taken from input; lifecycle fields (status, advertiser, reference,
  verification, dates) never are.
- Fields that do not apply are cleared: room fields when the type has no rooms, building fields
  when it has no building details, `rent_period` for sales.

## Completeness (checked on submit)
Arabic title (5–150) and description (30–5000), district (must belong to the city — composite
foreign key), price > 0, area > 0, coordinates inside Saudi Arabia, bedrooms and bathrooms for
types with rooms, rent period for rentals and none for sales.

## Authorization (PropertyPolicy)
| Ability | Rule |
|---|---|
| view | Published & unexpired: everyone. Otherwise: managing advertiser or `properties.view-unpublished` |
| create | `properties.create` |
| update / delete / changeStatus | (advertiser **and** `properties.manage-own`) or `properties.manage-any` |
| moderate | `properties.moderate` **and** not the listing's advertiser (applies to admins too) |
| viewHistory | managing advertiser or `properties.view-unpublished` |

API behaviour: a listing the caller cannot view returns **404**; a visible listing with a denied
action returns **403**.

## Records
Every transition writes `property_status_histories` and an `audit_logs` entry
(`property.status_changed`). Creation, edits (changed field names) and deletion are audited.
Transitions and edits lock the row (`SELECT … FOR UPDATE`) inside a transaction.
