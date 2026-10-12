# ADR 0002 — Roles and self-registration rules

- **Status:** Proposed — awaiting owner approval
- **Scope:** Phase 1, Step 6

| # | Decision | Rationale |
|---|---|---|
| 1 | Keep `buyer` and `renter` as separate roles with identical (empty) permissions | Matches the product brief; can diverge later without data migration. Alternative: merge into one "seeker" role. |
| 2 | Self-assignable at registration: `buyer` (default), `renter`, `owner` | Low-privilege; owners can create listings but publication still requires moderation. |
| 3 | `broker` and `developer` assigned only by an administrator | They imply professional/licensed status, which must be verified, not self-declared. |
| 4 | `staff` and `admin` are never self-assigned; first admin created via `users:assign-role` | No API path to privileged roles in Phase 1. |
| 5 | Admin receives every permission explicitly (no `Gate::before` bypass) | Every capability is visible, testable and auditable. |
| 6 | Advertising-license requirements for listings are handled with the property domain (Step 8) | Applies to listings, not to account roles. |
