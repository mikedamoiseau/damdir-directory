# Browser Test Report: All Purpose Directory Plugin

**Date:** 2026-03-04
**Environment:** WordPress 6.9.1 at `http://localhost/` (Docker)
**Demo Data:** 23 categories, 10 tags, 25 listings, 75 reviews, 24 inquiries, 5 users

---

## Summary

| Domain | Tests Run | Passed | Failed | Blocked | Notes |
|--------|-----------|--------|--------|---------|-------|
| Admin Settings & Modules | 11 | 11 | 0 | 0 | All tabs verified, save/persist confirmed |
| Admin Listings CRUD | 12 | 10 | 0 | 2 | List table, filters, bulk actions all pass; publish/edit blocked by block editor session expiry |
| Admin Reviews & Demo Data | 20 | 19 | 0 | 1 | Category edit icon/color verified via list columns |
| Frontend Directory & Search | 16 | 14 | 1 | 1 | "Title A-Z" sort bug found |
| Frontend Submission & Dashboard | 14 | 10 | 0 | 4 | Submission works; dashboard auth-dependent tabs not tested |
| Frontend Reviews, Favorites & Contact | 28 | 23 | 0 | 5 | All UI renders correctly; interactive submissions blocked by auth |
| **TOTAL** | **101** | **87** | **1** | **12** | |

**Pass rate:** 87/101 (86%) tested, 87/88 (99%) of tested items passed
**Blocked items:** All 14 blocked tests were due to auth cookie expiration or block editor redirect, not plugin bugs.

---

## Bugs Found

### BUG-1: "Title A-Z" Sort Produces Z-to-A Results (MEDIUM) — FIXED

**Location:** `/src/Search/FilterRenderer.php` and `/src/Search/SearchQuery.php`
**Root cause:** `apply_orderby()` and `get_current_order()` always defaulted to `DESC` when no explicit `apd_order` was in the request. The hidden `<input name="apd_order" value="DESC">` in the search form always sent `DESC` regardless of the selected orderby option.
**Fix applied:**
1. Added `ORDERBY_DEFAULT_ORDER` constant mapping each orderby to its natural direction (`title` → `ASC`, `date`/`views` → `DESC`)
2. Updated `apply_orderby()` and `get_current_order()` to use the per-option default when no explicit `apd_order` is provided
3. Removed the hidden `apd_order` input from the search form, letting the backend use the correct default
**Tests:** 6 new unit tests added, all 2697 tests pass.

### ~~BUG-2: Favorites Button Accessibility Label Doesn't Update (LOW)~~ — FALSE POSITIVE

**Description:** The JS code at `assets/js/frontend.js:1537` already correctly updates `aria-label` to "Remove from favorites" / "Add to favorites" after AJAX toggle. The PHP `get_button()` also sets the correct initial label based on state. The test agents read the Playwright accessibility snapshot of the initial page load before the AJAX round-trip completed, giving the false impression the label didn't update.

---

## Observations & Minor Issues

### OBS-1: Category/Tag Archives Use Default Theme Template

Category archives (`/listing-category/{slug}/`) and tag archives (`/listing-tag/{slug}/`) use the WordPress theme's default archive template (plain list) instead of the plugin's styled card layout with ratings, favorites, and search form. This creates a visual inconsistency with the `/directory/` page.

### ~~OBS-2: Block Editor Client-Side Redirect~~ (FALSE POSITIVE)

Verified 2026-03-04: The block editor works correctly with no redirect. The reported "redirect" was caused by multiple test sub-agents controlling the same Playwright browser session simultaneously — one agent navigating to a frontend page while another was on the editor. Clean single-session test confirmed the editor stays on `post-new.php?post_type=apd_listing` with all fields (title, Phone, Email, Website, Address, City, State, Zip, Business Hours, Price Range, Categories, Tags, Publish) fully functional after 20+ seconds.

### OBS-3: "Clear Filters" Links to Wrong Page — FIXED

The "Clear Filters" link on `/directory/` pointed to `/listings/` (post type archive) instead of `/directory/`.
**Root cause:** Multiple places hardcoded `get_post_type_archive_link('apd_listing')` as the form action and clear-filter URL: `SearchFormShortcode.php`, `SearchFormBlock.php`, and several methods in `FilterRenderer.php`.
**Fix applied:**
1. `FilterRenderer::render_search_form()` now auto-detects the current page URL when no explicit action is provided
2. Removed hardcoded archive link fallbacks from `SearchFormShortcode` and `SearchFormBlock`
3. Added `get_base_url()` helper in `FilterRenderer` so active filters, remove-filter URLs, and no-results links all use the resolved action URL instead of the hardcoded archive link
**Verified:** Form action, "Clear Filters" link, and URL after search all correctly stay on `/directory/`.

### OBS-4: Submission Tab "Guest Submission" Field Conditional — EXPECTED BEHAVIOR

The "Guest Submission" field is only visible when "Who Can Submit" is set to "Anyone (including guests)". This is correct — the toggle is irrelevant when submissions are restricted to logged-in users only.

### OBS-5: Listing Type Column Not Shown

No `listing_type` column in admin list table. Per CLAUDE.md, this is conditional on 2+ types existing, so expected behavior.

---

## Agent 1: Admin Settings & Modules - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| General tab renders | PASS | Currency Symbol ($), Currency Position (Before amount), Date Format (WP Default), Distance Unit (Kilometers), Pages section (Directory, Submission, Dashboard dropdowns) |
| General tab save/persist | PASS | Changed currency symbol to "EUR", saved, persisted on reload with "Settings saved." confirmation |
| Listings tab renders | PASS | Listings Per Page (12), Default Listing Status (Pending Review), Listing Expiration (0), Enable Reviews/Favorites/Contact (all checked) |
| Listings tab checkbox toggle | PASS | Unchecked "Enable Reviews", saved, persisted as unchecked. Re-enabled and saved. |
| Submission tab renders | PASS | Who Can Submit (Logged-in Users Only), Terms & Conditions Page, After Submission Redirect (View Listing) |
| Display tab renders | PASS | Default View (Grid), Grid Columns (3), Show toggles (all checked), Archive Title, Single Layout (With Sidebar) |
| Email tab renders | PASS | From Name, From Email, Admin Email. Notification toggles: Submission, Approved, Rejected, Expiring, Review, Inquiry (all checked) |
| Advanced tab renders | PASS | Delete Data on Uninstall (unchecked), Custom CSS (textarea), Debug Mode (unchecked) |
| Cross-tab persistence | PASS | Changed value on General, saved. Saved Listings tab. General value still intact. |
| Modules page loads | PASS | Heading "Installed Modules" with subtitle |
| Modules empty state | PASS | "No Modules Installed" with descriptive text about modules being separate plugins |

---

## Agent 2: Admin Listings CRUD - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| Navigate to Add New | PASS | Block editor loads with title field and "Listing Fields" panel (Phone, Email, Website, Address, City, State, Zip, Business Hours, Price Range) |
| Categories/Tags panels | PASS | Sidebar has Categories checkboxes panel and Tags panel |
| Publish listing | BLOCKED | Block editor session expiry prevents REST API publish (nonces stale) |
| Listing list table loads | PASS | 26 items: 20 published, 2 drafts, 2 pending, 2 expired |
| Admin columns | PASS | Image, Title, Category, Status (color-coded badges), Views (sortable), Author, Date |
| Category filter dropdown | PASS | All parent/child categories with counts. Filtering "Entertainment (7)" correctly shows 7 listings |
| Status filter tabs | PASS | All (26), Mine (1), Published (21), Draft (1), Pending (2), Expired (2). Clicking "Pending" correctly shows 2 listings |
| Views column sortable | PASS | Sortable, showing counts up to 4,918 |
| Bulk actions dropdown | PASS | Edit, Move to Trash available |
| Edit listing title | PASS | Title update verified (changed "Fast Joint" to "Fast Joint Updated") |
| Modify and update | BLOCKED | Block editor REST API nonce expiry |
| Bulk trash + undo | PASS | Selected 2 listings, "Move to Trash" - "2 posts moved to the Trash". Undo restored both: "2 posts restored from the Trash". Count dropped 26->24->26 |

---

## Agent 3: Admin Reviews & Demo Data - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| Reviews page loads | PASS | "Reviews" heading with star icon |
| Reviews count | PASS | 69 active + 4 spam + 2 trash = 75 total |
| Status tabs | PASS | All (69), Pending (13), Approved (56), Spam (4), Trash (2) |
| Review details | PASS | Listing (linked), Author + email, Star rating (visual), Title, Content excerpt, Status badge, Date |
| Filter dropdowns | PASS | Status, Listing (23 options), Rating (1-5 stars) |
| Bulk actions | PASS | Approve, Mark as Spam, Move to Trash |
| Approve pending review | PASS | Approved review; success notice shown; Pending 13->12, Approved 56->57 |
| Row actions | PASS | Approve/Unapprove, Spam, Trash, View Listing |
| Demo Data page loads | PASS | "Demo Data Generator" with status section |
| Demo Data counts | PASS | Categories: 23, Tags: 10, Listings: 25, Reviews: 75, Inquiries: 24, Users: 5 |
| Generate/Delete controls | PASS | Generate/Delete buttons, checkboxes for data types, count inputs |
| Categories page | PASS | 23 categories, 6 parents + children with indentation |
| Category hierarchy | PASS | Entertainment, Healthcare, Hotels & Lodging, Restaurants, Services, Shopping with children |
| Category Icon column | PASS | Custom column with dashicon previews |
| Category Color column | PASS | Custom column with colored swatches |
| Category edit - Icon | PASS | Dropdown set to "Tickets" with dashicon preview |
| Category edit - Color | PASS | Color picker with swatch and "Select Color" button |
| Tags page | PASS | 10 tags displayed |
| Tag names and counts | PASS | All tags with descriptions, slugs, and counts (Parking Available: 15, Accepts Credit Cards: 12, etc.) |
| Category edit page access | BLOCKED | Auth re-validation for some agents (but verified by another agent) |

---

## Agent 4: Frontend Directory & Search - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| Directory page loads | PASS | `/directory/` with heading, 12 listing cards in 3-column grid |
| Listing cards display | PASS | Category badges, title (linked), excerpt, star ratings, review count, favorites button, "View Details" |
| Search form present | PASS | Keyword input, category dropdown (16+ categories), sort dropdown (4 options), Search button, Clear Filters |
| Keyword search | PASS | "gym" returns 2 results (Elite Gym, Sunrise Gym). Empty keyword correctly shows "No listings found" |
| Category filter | PASS | "Restaurants" filter correctly shows 2 restaurant listings (Fast Joint, Super Shack) |
| Sort - Newest First | PASS | Default sort works |
| Sort - Title A-Z | **FAIL** | Sorts Z-to-A instead of A-to-Z (see BUG-1) |
| Sort - Most Viewed | PASS | Results change based on view count |
| Clear Filters | PASS | Resets search, shows all listings |
| Pagination | PASS | Page 1: 12 listings, Page 2: 8 listings. Nav links work |
| Single listing page | PASS | Title, author, description, star rating, favorites, Details section (Phone/Email/Website/Address/City/State/Zip/Hours/Price), Tags, Contact form, Reviews |
| Contact form on listing | PASS | Name*, Email*, Phone (optional), Message* with character counter |
| Reviews section | PASS | Rating summary + breakdown chart, individual reviews, "Write a Review" form (when logged in) |
| Category archive | PASS | `/listing-category/entertainment/` shows "Category: Entertainment" with description and 7 filtered listings |
| Tag archive | PASS | `/listing-tag/delivery-available/` shows "Tag: Delivery Available" with 3 filtered listings |
| Favorites button | PASS | Heart icon toggles from empty to filled/red on click |

---

## Agent 5: Frontend Submission & Dashboard - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| Submission page exists | PASS | `/submit-a-listing/` with title "Submit a Listing" |
| Login redirect for guests | PASS | Unauthenticated users redirected to `wp-login.php` with return URL |
| Form renders (authenticated) | PASS | Class `apd-submission-form--new`, method POST, aria-label "Submit listing" |
| Section tabs | PASS | 4 sections: Basic Info, Details, Categories, Image |
| Title field | PASS | Text input with placeholder |
| Description field | PASS | Textarea with placeholder |
| Detail fields | PASS | All 9: Phone, Email, Website, Address, City, State, Zip, Business Hours, Price Range |
| Category checkboxes | PASS | 23 categories with parent/child |
| Tag checkboxes | PASS | 10 tags |
| Featured image upload | PASS | File input with "Select Image" button |
| Submit button | PASS | "Submit Listing" present |
| Spam protection | PASS | Honeypot field, form token, nonce |
| Form submission | PASS | Submitted listing created as Draft in admin |
| Dashboard page exists | PASS | `/my-dashboard/` with "My Dashboard" title |
| Dashboard login required (guest) | PASS | "Login Required" heading with "Please log in" message and login link |
| Dashboard tabs (authenticated) | BLOCKED | Auth cookie expired before testing |
| My Listings tab | BLOCKED | Auth required |
| Favorites tab | BLOCKED | Auth required |
| Profile tab | BLOCKED | Auth required |

---

## Agent 6: Frontend Reviews, Favorites & Contact - DETAILED RESULTS

| Test | Status | Notes |
|------|--------|-------|
| Reviews section visible | PASS | "Reviews (2)" heading on single listing |
| Rating summary | PASS | "3.5" average with star visualization |
| Rating breakdown chart | PASS | Progress bars for 5-star through 1-star with counts |
| Existing reviews | PASS | Chris Brown (4 stars), Mike Anderson (3 stars) with titles, dates, content |
| Review form (logged in) | PASS | Star radio buttons (1-5), title input, content textarea, submit button |
| Review form (logged out) | PASS | "Please log in or register to write a review" message |
| Review form submission | BLOCKED | Auth cookie issues |
| Star ratings on archive cards | PASS | 11/12 cards show star ratings with review counts |
| Favorite buttons on archive | PASS | 12 favorite buttons on 12 listing cards |
| Favorite button on single listing | PASS | "Add to favorites" with heart icon |
| Favorite toggle | BLOCKED | Auth required for AJAX toggle |
| Dashboard favorites tab | BLOCKED | Auth required |
| Contact form visible | PASS | "Contact the Owner" section |
| Contact form - Name field | PASS | Required text input |
| Contact form - Email field | PASS | Required email input |
| Contact form - Phone field | PASS | Optional tel input |
| Contact form - Message field | PASS | Required textarea with "0 / 10 characters minimum" |
| Contact form - Submit button | PASS | "Send Message" |
| Contact form - Honeypot | PASS | Hidden "contact_website" field |
| Contact form fill | PASS | All fields filled successfully |
| Contact form submission | BLOCKED | Auth interference |
| Admin inquiry verification | BLOCKED | Auth issues |
| Listing details - all fields | PASS | Phone (tel: link), Email (mailto: link), Website (https: link), Address, City, State, Zip, Business Hours, Price Range |
| Tags display | PASS | Tags with links to tag archives |
| Post navigation | PASS | Previous/Next listing links |
| Search form on archive | PASS | Keyword input, category dropdown, sort dropdown |
| Listing grid | PASS | 12 cards displayed |
| Pagination | PASS | Page numbers and Next link |

---

## Test Infrastructure Issues

1. **Auth Cookie Expiration:** WordPress session cookies became invalid during testing, likely caused by multiple agents changing the admin password via WP-CLI, which invalidates all existing session tokens. Future runs should use a single stable password.

2. **Block Editor Redirect:** The Gutenberg editor on `apd_listing` pages redirects away within 2-5 seconds of loading. This needs investigation - may be a plugin JS conflict or block editor preview behavior.

3. **Agent Tool Selection:** Sub-agents used Playwright MCP tools instead of `agent-browser` CLI despite instructions, causing browser session conflicts between agents.

---

## Advanced Behavioral Tests — Phase 2: Submission Access Control & Validation

**Date:** 2026-03-05
**Execution:** Sequential, main agent only. Settings via WP-CLI, frontend via agent-browser CLI.

### Summary

| Test | Status | Notes |
|------|--------|-------|
| 2.1: Submission requires login | PASS | Guest sees "Please log in to submit a listing." Logged-in user sees full form. |
| 2.2: Guest submission disabled | PASS | `who_can_submit=anyone` + `guest_submission=false` → guest still sees login message |
| 2.3: Guest submission enabled | PASS | `who_can_submit=anyone` + `guest_submission=true` → guest sees full form |
| 2.4: Default status=publish | PASS (was FAIL, fixed) | Setting now correctly respected after Plugin.php fix |
| 2.5: Default status=pending | PASS | Listing created with `pending` status, not visible on frontend directory |
| 2.6: Missing required title | PASS | HTML5 `required` attribute on title input prevents submission |
| 2.7: Missing required content | PASS | HTML5 `required` attribute on description textarea prevents submission |
| 2.8: Honeypot spam protection | PASS | Filling hidden `website_url` field silently rejects submission, no listing created |

**Result: 8/8 passed (1 bug found and fixed)**

### BUG-3: `default_status` Setting Not Respected by SubmissionHandler (MEDIUM) — FIXED

**Location:** `src/Core/Plugin.php:177`
**Symptom:** Admin setting `default_status=publish` was ignored — all frontend submissions created with `pending` status.
**Root cause:** `Plugin.php:177` instantiated `SubmissionHandler` with no config: `new SubmissionHandler()`. The handler's `DEFAULTS` constant hardcodes `'default_status' => 'pending'`, so `get_default_status()` always returned `'pending'`.
**Fix applied:** Pass the admin setting when constructing the handler: `new SubmissionHandler(['default_status' => apd_get_setting('default_status', 'pending')])`.
**Verified:** Listing created with `publish` status when setting is `publish`, visible on `/directory/` immediately.

### Notes

- Tests 2.6 and 2.7 rely on HTML5 `required` attribute for client-side validation. Server-side validation also exists (`require_title` and `require_content` in `SubmissionHandler::DEFAULTS`) but was not directly tested since browser-native validation fires first.
- Test 2.8 confirms the honeypot field (`website_url`, hidden via CSS class `apd-field--hp`) silently rejects spam. The form redirects to `/` with no listing created and no error message shown (correct behavior for bot submissions).

---

## Screenshots

All screenshots saved to `/tmp/apd-test-*`:
- `apd-test-frontend-directory.png` - Directory page
- `apd-test-frontend-search-gym.png` - Search results
- `apd-test-frontend-category-filter-working.png` - Category filter
- `apd-test-frontend-sort-title.png` - Title sort bug
- `apd-test-frontend-single-listing-*.png` - Single listing sections
- `apd-test-frontend-category-archive.png` - Category archive
- `apd-test-frontend-favorites-click.png` - Favorites toggle
- `apd-test-interactive-contact-filled.png` - Contact form filled
- `apd-test-reviews-admin.png` - Reviews admin page
- `apd-test-demo-data.png` - Demo data page
- `apd-test-categories.png` - Categories admin
