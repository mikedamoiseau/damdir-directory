# Browser Testing Plan 2: Advanced Behavioral Flows

## Context

Plan 1 tested surface-level UI rendering (do pages load, do fields exist, do buttons work). This plan tests **behavioral flows** — settings that change behavior, validation errors, access control, cross-feature journeys, and edge cases.

**Prerequisites:** Plan 1 demo data still exists (23 categories, 10 tags, 25 listings, 75 reviews, 24 inquiries, 5 users).
**Credentials:** admin / admin
**Environment:** WordPress 6.9.1 at `http://localhost/` (Docker)

---

## Lessons from Plan 1

1. **Sub-agents used Playwright MCP tools** instead of `agent-browser` CLI — causing browser conflicts
2. **Multiple agents changing admin password** invalidated all session cookies
3. **Sequential is better for behavioral tests** — most tests require: change setting → verify frontend effect

**Strategy for Plan 2:** The main agent runs all tests sequentially using `agent-browser` CLI via Bash. Settings changes are done via **WP-CLI** (faster and more reliable than navigating admin UI), while frontend verification uses `agent-browser`. No sub-agents for browser work.

---

## Execution Flow

### Phase 0: Auth Setup
1. Ensure admin password is `admin` via WP-CLI
2. Login via `agent-browser`, save state
3. Verify auth works by navigating to an admin page

### Phase 1: Settings-Driven Feature Toggles
Test that disabling features removes them from the frontend.

### Phase 2: Submission Access Control & Validation
Test permission gating, field validation, spam protection.

### Phase 3: Review Validation
Test duplicate reviews, content validation, login requirements.

### Phase 4: Contact Form Validation
Test required fields, invalid data, message length.

### Phase 5: Favorites Access Control
Test logged-in vs logged-out behavior.

### Phase 6: Cross-Feature Journeys
End-to-end flows combining multiple features.

### Phase 7: Report
Compile results, append to existing `docs/browser-test-report.md`.

---

## Phase 1: Settings-Driven Feature Toggles

For each test: change setting via WP-CLI → verify frontend via agent-browser → restore setting.

WP-CLI pattern:
```bash
# Read current value
docker exec wp-all-purpose-directory-web-1 wp eval "echo apd_get_setting('enable_reviews');" --allow-root

# Set value
docker exec wp-all-purpose-directory-web-1 wp eval "\$opts = get_option('apd_options'); \$opts['enable_reviews'] = false; update_option('apd_options', \$opts);" --allow-root
```

### Test 1.1: Disable Reviews → verify reviews section hidden on single listing
- **Set:** `enable_reviews` = `false`
- **Verify:** Navigate to a single listing → snapshot → reviews section should NOT be present
- **Restore:** `enable_reviews` = `true`
- **Verify restore:** Reviews section visible again

### Test 1.2: Disable Favorites → verify favorite buttons hidden
- **Set:** `enable_favorites` = `false`
- **Verify:** Navigate to `/directory/` → snapshot → no favorite buttons on listing cards
- **Verify:** Navigate to single listing → no favorite button
- **Restore:** `enable_favorites` = `true`

### Test 1.3: Disable Contact Form → verify contact form hidden on single listing
- **Set:** `enable_contact_form` = `false`
- **Verify:** Navigate to single listing → no "Contact the Owner" section
- **Restore:** `enable_contact_form` = `true`

### Test 1.4: Change Listings Per Page → verify pagination changes
- **Set:** `listings_per_page` = `5`
- **Verify:** Navigate to `/directory/` → only 5 listing cards on page 1 → pagination shows more pages
- **Restore:** `listings_per_page` = `12`

### Test 1.5: Change Default View → verify grid vs list
- **Set:** `default_view` = `list`
- **Verify:** Navigate to `/directory/` → listing display uses list layout (CSS class check)
- **Restore:** `default_view` = `grid`

---

## Phase 2: Submission Access Control & Validation

### Test 2.1: Submission requires login (default: `who_can_submit` = `logged_in`)
- **As guest (no auth):** Navigate to `/submit-a-listing/` → verify login redirect or "must be logged in" message
- **As logged-in user:** Navigate to `/submit-a-listing/` → verify form renders

### Test 2.2: Guest submission disabled when `who_can_submit` = `anyone` but `guest_submission` = `false`
- **Set:** `who_can_submit` = `'anyone'`, `guest_submission` = `false`
- **As guest:** Navigate to `/submit-a-listing/` → verify login still required
- **Restore:** `who_can_submit` = `'logged_in'`

### Test 2.3: Guest submission enabled
- **Set:** `who_can_submit` = `'anyone'`, `guest_submission` = `true`
- **As guest:** Navigate to `/submit-a-listing/` → verify form renders without login
- **Restore:** `who_can_submit` = `'logged_in'`, `guest_submission` = `false`

### Test 2.4: Default status = `publish` → listing immediately visible
- **Set:** `default_status` = `'publish'`
- **As admin:** Submit a listing titled "Publish Test Listing" via the frontend form
- **Verify:** Navigate to `/directory/` → search for "Publish Test" → listing appears
- **Restore:** `default_status` = `'pending'`

### Test 2.5: Default status = `pending` → listing NOT visible on frontend
- **Set:** `default_status` = `'pending'` (already default)
- **As admin:** Submit a listing titled "Pending Test Listing"
- **Verify:** Navigate to `/directory/` → search for "Pending Test" → listing NOT found
- **Verify via WP-CLI:** Listing exists with status `pending`

### Test 2.6: Submit with missing required title
- **As admin:** Navigate to submission form → leave title empty → fill content → submit
- **Verify:** Error message about required title field

### Test 2.7: Submit with missing required content
- **As admin:** Navigate to submission form → fill title → leave content empty → submit
- **Verify:** Error message about required content

### Test 2.8: Spam protection — honeypot triggered
- **Verify via WP-CLI/eval:** Submit form data with `website_url` field filled (honeypot)
- **Expected:** Submission rejected silently or with generic error

---

## Phase 3: Review Validation

Use a single listing page for all review tests.

### Test 3.1: Submit valid review
- **As admin:** Navigate to a listing → find review form → select 4 stars → enter title → enter content (20+ chars) → submit
- **Verify:** Success message or review appears (may show "pending moderation")

### Test 3.2: Submit review without rating
- **As admin:** Navigate to listing → leave star rating unselected → enter content → submit
- **Verify:** Error message about required rating

### Test 3.3: Submit review with content too short
- **As admin:** Navigate to listing → select rating → enter content "Short" (5 chars, min is 10) → submit
- **Verify:** Error message about minimum content length

### Test 3.4: Duplicate review (same user, same listing)
- **As admin:** Navigate to same listing where Test 3.1 review was submitted → try to submit another review
- **Verify:** Error message "You have already reviewed this listing" OR review form not shown

### Test 3.5: Review form hidden when not logged in (default behavior)
- **As guest:** Navigate to a listing → scroll to reviews section
- **Verify:** Review form NOT shown, "Please log in or register to write a review" message displayed

---

## Phase 4: Contact Form Validation

Use a single listing page for all contact tests.

### Test 4.1: Submit contact with all valid fields
- **Navigate to listing → fill name, valid email, message (20+ chars) → submit
- **Verify:** Success message

### Test 4.2: Submit contact with empty name
- **Leave name empty → fill email and message → submit
- **Verify:** Error about required name

### Test 4.3: Submit contact with invalid email
- **Fill name → enter "notanemail" in email → fill message → submit
- **Verify:** Error about invalid email

### Test 4.4: Submit contact with message too short
- **Fill name and email → enter "Hi" as message (2 chars, min is 10) → submit
- **Verify:** Error about minimum message length

### Test 4.5: Submit contact with all fields empty
- **Click submit without filling anything
- **Verify:** Multiple error messages

---

## Phase 5: Favorites Access Control

### Test 5.1: Favorite as logged-in user
- **As admin:** Navigate to `/directory/` → click favorite button on a listing
- **Verify:** Button state changes (heart fills, text changes)
- **Navigate to dashboard favorites tab → verify listing appears

### Test 5.2: Unfavorite
- **From dashboard favorites → click remove/unfavorite
- **Verify:** Listing removed from favorites list

### Test 5.3: Favorite as guest (default: disabled)
- **As guest (clear auth):** Navigate to `/directory/`
- **Click favorite button
- **Verify:** Favorite does NOT work (no state change, or login prompt)

---

## Phase 6: Cross-Feature Journeys

### Test 6.1: Full Listing Lifecycle
1. Set `default_status` = `'publish'` via WP-CLI
2. As admin, submit a new listing "Journey Test Cafe" with category "Restaurants", phone, email
3. Navigate to `/directory/` → search for "Journey Test" → verify it appears
4. Click into listing → verify all details display
5. Submit a 5-star review on the listing
6. Click favorite on the listing
7. Navigate to dashboard → verify listing in "My Listings"
8. Navigate to dashboard favorites → verify listing in favorites
9. Clean up: trash the listing via WP-CLI
10. Restore `default_status` = `'pending'`

### Test 6.2: Category Filter + Keyword Search Combined
1. Navigate to `/directory/`
2. Select "Restaurants" category AND enter keyword "fast"
3. Verify results show only restaurants matching "fast" (e.g., "Fast Joint")

### Test 6.3: Expired Listing Not Visible
1. Via WP-CLI: set a listing's status to `expired`
2. Navigate to `/directory/` → verify listing NOT shown
3. Search for the listing by title → verify NOT found
4. Via WP-CLI: restore listing to `publish`
5. Verify listing appears again

---

## Phase 7: Report

Append results to `docs/browser-test-report.md` as a new section:
- Title: "## Advanced Behavioral Tests (Plan 2)"
- Summary table with pass/fail/blocked counts
- Detailed results per phase
- New bugs found (if any)
- Cross-reference with Plan 1 bugs

---

## Test Count Summary

| Phase | Tests | Description |
|-------|-------|-------------|
| Phase 1 | 5 | Settings-driven feature toggles |
| Phase 2 | 8 | Submission access control & validation |
| Phase 3 | 5 | Review validation |
| Phase 4 | 5 | Contact form validation |
| Phase 5 | 3 | Favorites access control |
| Phase 6 | 3 | Cross-feature journeys |
| **Total** | **29** | |

---

## Technical Notes

- **Settings changes via WP-CLI** — faster and more reliable than navigating admin UI
- **Frontend verification via agent-browser** — all `agent-browser` commands run from main agent via Bash
- **No sub-agents for browser work** — avoids Playwright MCP tool confusion from Plan 1
- **Auth managed once** — login at start, don't change password during tests
- **Guest tests** — use a separate `agent-browser` session without auth state loaded
- **Cleanup after each test** — restore settings to defaults so tests don't affect each other
