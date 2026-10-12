# Authentication (v1)

Strategy (ADR 0001, decision 5): **Laravel Sanctum bearer tokens** for all API
clients now. One guard (`auth:sanctum`) serves every client; no separate auth
implementations.

## Endpoints

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/v1/auth/register` | — (throttled) | Create account, returns user + token (201) |
| POST | `/api/v1/auth/login` | — (throttled) | Returns user + token |
| POST | `/api/v1/auth/logout` | Bearer | Revoke the current token |
| POST | `/api/v1/auth/logout-all` | Bearer | Revoke all tokens (all devices) |
| GET | `/api/v1/me` | Bearer, active account | Current user's profile |

Send the token as `Authorization: Bearer <access_token>`.

### Register
Body: `name`, `email`, `password`, `password_confirmation`, optional `phone`
(Saudi mobile, normalized to `+9665XXXXXXXX`), optional `preferred_locale`
(`ar`|`en`), optional `device_name`.
Passwords: at least 8 characters with letters and numbers; in production, passwords
found in known breaches are rejected.

### Login
Body: `email`, `password`, optional `device_name` (shown in future device lists).

### Token response
```json
"data": {
  "user": { "id": "01j…", "name": "…", "email": "…", "email_verified": false,
            "phone": "+966512345678", "phone_verified": false,
            "preferred_locale": "ar", "status": "active", "created_at": "…" },
  "token": { "access_token": "1|tml_…", "token_type": "Bearer", "expires_at": "…" }
}
```

## Security behaviour

- **Expiry:** tokens expire after `SANCTUM_TOKEN_EXPIRATION_MINUTES` (default 30 days).
  Expired tokens are pruned daily by the scheduler.
- **Revocation:** `logout` (one device) and `logout-all` (every device).
- **Account status:** only `active` accounts can log in or use protected endpoints.
  `suspended`/`deactivated` accounts get `403 account_inactive`, even with a
  previously issued token. Status can never be set by the client.
- **Login protection:** wrong password and unknown email return the identical
  `422` error with equalized timing. After 5 failures per email+IP, login is
  locked for 60 s (`429` + `Retry-After`). Register/login are also limited to
  `AUTH_RATE_LIMIT_PER_MINUTE` (default 10) per IP.
- **Token storage:** mobile apps must keep tokens in secure storage
  (Keychain / Android Keystore), never in plain preferences or logs.

## Browser clients (planned, not yet enabled)
For the first-party Next.js website and React dashboard (Phase 2/3, after approval),
Sanctum's **SPA cookie mode** will be enabled: session cookies instead of tokens,
CSRF protection via `GET /sanctum/csrf-cookie` + `X-XSRF-TOKEN`,
`SANCTUM_STATEFUL_DOMAINS` and `CORS_SUPPORTS_CREDENTIALS=true` for the exact
frontend origins. Same guard, same user model, same authorization rules.

## Not yet implemented
- Password reset and email verification — Step 5.2.
- Phone verification and phone login — require an SMS provider (to be approved);
  the schema already has `phone_verified_at`.
- Roles assigned at registration — Step 6.
