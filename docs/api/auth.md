# Authentication (v1)

Strategy (ADR 0001, decision 5): **Laravel Sanctum bearer tokens** for all API
clients now. One guard (`auth:sanctum`) serves every client; no separate auth
implementations.

## Endpoints

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/v1/auth/register` | — (throttled) | Create account; returns user + token (201); sends verification email |
| POST | `/api/v1/auth/login` | — (throttled) | Returns user + token |
| POST | `/api/v1/auth/logout` | Bearer | Revoke the current token |
| POST | `/api/v1/auth/logout-all` | Bearer | Revoke all tokens (all devices) |
| POST | `/api/v1/auth/forgot-password` | — (throttled) | Request a reset link (same response for any email) |
| POST | `/api/v1/auth/reset-password` | — (throttled) | Set a new password with the emailed token; revokes all tokens |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | Signed link | Verify email (link from the verification email) |
| POST | `/api/v1/auth/email/verification-notification` | Bearer (6/min) | Resend the verification email |
| GET | `/api/v1/me` | Bearer, active account | Current user's profile |

Send tokens as `Authorization: Bearer <access_token>`.

### Register
Body: `name`, `email`, `password`, `password_confirmation`, optional `phone`
(Saudi mobile, normalized to `+9665XXXXXXXX`), optional `preferred_locale`
(`ar`|`en`), optional `device_name`.
Optional `account_type`: `buyer` (default), `renter` or `owner` (see [authorization](../architecture/authorization.md)).
Passwords: at least 8 characters with letters and numbers; in production, passwords
found in known breaches are rejected.

### Login
Body: `email`, `password`, optional `device_name`.

### Token response
```json
"data": {
  "user": { "id": "01j…", "name": "…", "email": "…", "email_verified": false,
            "phone": "+966512345678", "phone_verified": false,
            "preferred_locale": "ar", "status": "active", "created_at": "…" },
  "token": { "access_token": "1|tml_…", "token_type": "Bearer", "expires_at": "…" }
}
```

### Password reset
1. `POST /auth/forgot-password` with `email`. Always `200` with the same message,
   whether or not the account exists. At most one email per account per 60 s.
2. The email links to `FRONTEND_PASSWORD_RESET_URL`
   (default `http://localhost:3000/reset-password?token={token}&email={email}`).
   The website or app reads `token` and `email` from that link.
3. `POST /auth/reset-password` with `token`, `email`, `password`,
   `password_confirmation`. Tokens expire after 60 minutes and work once.
   Success revokes **all** API tokens; the user logs in again.
   Invalid token and unknown email return the same `422` (`errors.token`).

### Email verification
- Sent automatically on registration, in the user's `preferred_locale`.
- The link is signed, expires after 60 minutes, contains the user's **public ID**
  (never the internal ID) and works without a bearer token.
- Invalid, tampered or expired links return `403 invalid_verification_link`;
  the user can request a new one via `POST /auth/email/verification-notification`.
- Verification is recorded (`email_verified` in the user resource). Which actions
  will **require** a verified email (e.g. publishing listings) is decided with the
  property domain (Step 8).

## Security behaviour

- **Expiry:** tokens expire after `SANCTUM_TOKEN_EXPIRATION_MINUTES` (default 30 days).
  Expired tokens are pruned daily by the scheduler.
- **Revocation:** `logout` (one device), `logout-all` (every device), and automatically
  on password reset.
- **Account status:** only `active` accounts can log in or use protected endpoints.
  `suspended`/`deactivated` accounts get `403 account_inactive`, even with a
  previously issued token. Status can never be set by the client.
- **No account enumeration:** login, forgot-password and reset-password give the
  same response for unknown emails as for existing ones.
- **Throttling:** after 5 failed logins per email+IP, login is locked for 60 s
  (`429` + `Retry-After`). All unauthenticated auth endpoints are limited to
  `AUTH_RATE_LIMIT_PER_MINUTE` (default 10) per IP.
- **Token storage:** mobile apps must keep tokens in secure storage
  (Keychain / Android Keystore), never in plain preferences or logs.

## Browser clients (planned, not yet enabled)
For the first-party Next.js website and React dashboard (Phase 2/3, after approval),
Sanctum's **SPA cookie mode** will be enabled: session cookies instead of tokens,
CSRF protection via `GET /sanctum/csrf-cookie` + `X-XSRF-TOKEN`,
`SANCTUM_STATEFUL_DOMAINS` and `CORS_SUPPORTS_CREDENTIALS=true` for the exact
frontend origins. Same guard, same user model, same authorization rules.

## Known limitations
- Emails are sent synchronously (no queue worker yet). A mail-provider outage would
  make registration fail. Queue worker + queued notifications are required before production.
- Laravel's default email layout is left-to-right; Arabic text is correct but
  left-aligned. An RTL email theme is planned with Phase 3 notifications.
- Phone verification and phone login require an SMS provider (to be approved);
  the schema already has `phone_verified_at`.
