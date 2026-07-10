# TODOS

## Infrastructure

### Verify 419/session-middleware behavior when the session store is unreachable

**What:** Confirm what Laravel actually renders when the session store itself (not just an expired/mismatched token) is down — does it cleanly surface a 500, or something uglier before reaching the 419 handler?

**Why:** Surfaced during `/plan-eng-review` of the themed error pages plan (2026-07-10). Pre-existing Laravel framework behavior, not introduced by that change, but currently unverified on this app.

**Context:** aguet.dev now has themed 403/404/419/429/500/503 error views (`resources/views/errors/`). The 419 page assumes the session layer is otherwise healthy. If the session store (file/DB/redis, whichever this app uses) is down, session middleware runs before the 419 exception handler — worth confirming this surfaces as a themed 500 rather than a raw framework error.

**Effort:** S
**Priority:** P3
**Depends on:** None
