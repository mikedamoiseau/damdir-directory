# Browser Testing Plan: All Purpose Directory Plugin

## Context

The All Purpose Directory WordPress plugin is deployed at `http://localhost/` via `bin/sync-to-wordpress-deploy.sh`. The site currently has no listings or demo data. We need to perform comprehensive real-user browser testing of all plugin features using `agent-browser` (CLI tool), organized into parallel sub-agents for speed.

**Credentials:** admin / admin
**Auth state saved:** `/tmp/apd-admin-auth.json`

---

## Execution Strategy

### Phase 0: Setup (Sequential - Main Agent)
Generate demo data through the admin UI before any tests can run. This creates users, categories, tags, listings, reviews, inquiries, and favorites.

### Phase 1: Parallel Testing (Sub-Agents)
After demo data exists, launch **6 sub-agents in parallel**, each with its own named `agent-browser` session to avoid conflicts. Each agent tests one domain and reports results.

### Phase 2: Cleanup & Report (Main Agent)
Collect all sub-agent results and produce a consolidated test report.

---

## Phase 0: Generate Demo Data

**Run in main agent before spawning sub-agents.**

1. Load admin auth state from `/tmp/apd-admin-auth.json`
2. Navigate to `http://localhost/wp-admin/admin.php?page=apd-demo-data`
3. Click "Generate" for the General tab (creates categories, tags, listings, reviews, inquiries, favorites)
4. Also generate demo users (shared section above tabs)
5. Verify status shows expected counts (~5 users, 21 categories, 10 tags, 25 listings)
6. Close the browser session

---

## Phase 1: Parallel Sub-Agent Test Suites

### Agent 1: Admin Settings & Modules (`--session settings`)

**Settings Page** (`/wp-admin/admin.php?page=apd-settings`):
- [ ] General tab: Verify currency symbol, currency position, date format, distance unit fields render and save
- [ ] Listings tab: Verify listings per page, default status, expiration days, enable reviews/favorites/contact toggles
- [ ] Submission tab: Verify who can submit, guest submission, terms page, redirect after fields
- [ ] Display tab: Verify default view, grid columns, show toggles, archive title, single layout
- [ ] Email tab: Verify from name/email, admin email, notification toggles
- [ ] Advanced tab: Verify delete data, custom CSS, debug mode
- [ ] Tab switching preserves unsaved changes on other tabs
- [ ] Save button works and shows success notice

**Modules Page** (`/wp-admin/admin.php?page=apd-modules`):
- [ ] Page loads with correct heading
- [ ] Empty state message shows (no modules installed)
- [ ] Table structure is correct (columns: icon, name, description, version, author)

### Agent 2: Admin Listings CRUD (`--session listings`)

**Create Listing:**
- [ ] Navigate to Add New Listing
- [ ] Fill in title, content
- [ ] Assign a category and tag
- [ ] Fill in custom meta fields (if visible in the meta box)
- [ ] Publish the listing
- [ ] Verify success notice

**Listing List Table:**
- [ ] Navigate to All Listings
- [ ] Verify listings appear (from demo data + created listing)
- [ ] Verify admin columns: thumbnail, title, category, status, views, date
- [ ] Test category filter dropdown
- [ ] Test status filter tabs (Published, Pending, Draft, Expired)
- [ ] Test sorting by views column

**Edit Listing:**
- [ ] Click edit on an existing listing
- [ ] Modify title and a field
- [ ] Update and verify changes saved

**Bulk Actions:**
- [ ] Select multiple listings
- [ ] Move to Trash via bulk action

### Agent 3: Admin Reviews & Demo Data (`--session reviews`)

**Review Moderation** (`/wp-admin/admin.php?page=apd-reviews`):
- [ ] Page loads with reviews from demo data
- [ ] Status tabs show counts (All, Pending, Approved)
- [ ] View review details (listing, author, rating, content)
- [ ] Approve a pending review (if any)
- [ ] Reject/spam a review
- [ ] Verify status changes reflect in tab counts

**Demo Data Page** (`/wp-admin/admin.php?page=apd-demo-data`):
- [ ] Page loads with status counts showing generated data
- [ ] Status counts match expected values
- [ ] Verify data types displayed (users, categories, tags, listings, reviews, inquiries, favorites)

**Categories & Tags Admin:**
- [ ] Navigate to Listings > Categories
- [ ] Verify demo categories exist with parent/child hierarchy
- [ ] Check category icon and color meta fields
- [ ] Navigate to Listings > Tags
- [ ] Verify demo tags exist

### Agent 4: Frontend Directory & Search (`--session frontend`)

**Directory/Archive Page** (`/directory/`):
- [ ] Page loads with listing cards
- [ ] Listings display thumbnails, titles, excerpts, categories
- [ ] Search form is present with keyword, category filter, sort
- [ ] Keyword search filters results
- [ ] Category dropdown filters results
- [ ] Sort by options work (Newest, Title A-Z, Most Viewed, Random)
- [ ] Clear Filters link works
- [ ] Pagination works (if >listings per page)

**Single Listing Page:**
- [ ] Click a listing from the directory
- [ ] Listing title, content, and meta fields display
- [ ] Categories and tags show
- [ ] Related listings section appears
- [ ] Review section is visible
- [ ] Contact form is visible
- [ ] View counter increments on revisit

**Category Archive:**
- [ ] Click a category link
- [ ] Filtered listings display for that category
- [ ] Category name shows in heading

### Agent 5: Frontend Submission & Dashboard (`--session submission`)

**Login as Demo User (or admin):**
- [ ] Navigate to submission page
- [ ] If not logged in, verify login prompt
- [ ] Log in as admin

**Submission Form** (`/submit-a-listing/`):
- [ ] Form renders with all fields (title, content, categories, tags, featured image)
- [ ] Fill in required fields and submit
- [ ] Verify success message
- [ ] Verify listing appears in admin (pending or published based on settings)

**User Dashboard** (`/my-dashboard/`):
- [ ] Dashboard loads with tabs (My Listings, Add New, Favorites, Profile)
- [ ] My Listings tab shows user's listings with actions
- [ ] Statistics panel shows counts
- [ ] Add New tab shows submission form
- [ ] Favorites tab shows favorited listings (from demo data)
- [ ] Profile tab shows profile editing form
- [ ] Edit profile fields and save

### Agent 6: Frontend Reviews, Favorites & Contact (`--session interactive`)

**Reviews (on a single listing page):**
- [ ] Navigate to a listing with existing reviews
- [ ] Reviews section shows review items with ratings
- [ ] Rating summary/average displays
- [ ] Review form is visible when logged in
- [ ] Submit a new review (star rating + content)
- [ ] Verify review appears (or shows pending message)

**Favorites:**
- [ ] Navigate to directory page
- [ ] Click favorite button/icon on a listing
- [ ] Verify favorite state toggles
- [ ] Navigate to dashboard favorites tab
- [ ] Verify favorited listings appear
- [ ] Remove a favorite and verify

**Contact Form (on single listing page):**
- [ ] Navigate to a listing
- [ ] Contact form renders with fields (name, email, message)
- [ ] Fill in and submit contact form
- [ ] Verify success message
- [ ] Check admin inquiries (navigate to admin > listing inquiries)

---

## Phase 2: Results Collection

After all sub-agents complete:
1. Collect pass/fail results from each agent
2. Produce a consolidated test report with:
   - Total tests run / passed / failed
   - Failed test details with screenshots
   - Any bugs or issues discovered
3. Save report

---

## Technical Notes

- Each sub-agent uses a unique `--session` name to avoid browser conflicts
- Each sub-agent loads admin auth state from `/tmp/apd-admin-auth.json` when admin access is needed
- Frontend-only tests (Agent 4 partial) don't need auth state
- Sub-agents use `agent-browser` CLI commands (not Playwright MCP tools)
- All agents close their sessions when done (`agent-browser --session X close`)
