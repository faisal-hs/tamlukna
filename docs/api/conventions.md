# Tamlukna API Conventions (v1)

Base path: `/api/v1`. All responses are JSON (`Content-Type: application/json`).

## Envelope

Success:
```json
{ "success": true, "message": "Operation completed successfully.", "data": {}, "meta": {} }
```

Error:
```json
{ "success": false, "message": "The given data was invalid.", "code": "validation_failed",
  "data": null, "errors": { "field": ["..."] }, "meta": {} }
```

- `message` is human-readable and localized; clients must not parse it.
- `code` is stable and machine-readable; clients branch on it.
- `errors` appears only for `validation_failed` (field → messages).

| HTTP | code |
|---|---|
| 400 | bad_request |
| 401 | unauthenticated |
| 403 | forbidden |
| 404 | not_found |
| 405 | method_not_allowed |
| 409 | conflict |
| 413 | payload_too_large |
| 415 | unsupported_media_type |
| 422 | validation_failed |
| 429 | too_many_requests (`Retry-After` header) |
| 500 | server_error |
| 503 | service_unavailable |

Internal details (exception messages, model names, traces) are never returned.
`meta.debug` appears only when `APP_DEBUG=true` and must never be enabled in production.

## Pagination

Query: `page` (≥1), `per_page` (1–50, default 15).
```json
"meta": { "pagination": { "current_page": 1, "per_page": 15, "total": 120,
  "last_page": 8, "from": 1, "to": 15, "has_more_pages": true } }
```

## Localization

Send `Accept-Language: ar` or `en` (regional variants such as `ar-SA` are accepted).
The response includes `Content-Language`. Unsupported languages fall back to the default locale.

## Request IDs

Every API response carries `X-Request-Id`. Clients may send their own
(8–64 chars, `A-Z a-z 0-9 . _ -`); otherwise the server generates a UUID.
Quote it when reporting problems; it is attached to server logs.

## Rate limiting

Default: 60 requests/minute per authenticated user, or per IP when anonymous
(`API_RATE_LIMIT_PER_MINUTE`). Sensitive endpoints get stricter limits.

## CORS

Deny-by-default. Allowed browser origins are listed in `CORS_ALLOWED_ORIGINS`
(comma-separated, exact match).

## Health

- `GET /up` — liveness (the PHP application is serving requests).
- `GET /api/v1/health` — readiness (database and Redis reachable); 503 when degraded.
