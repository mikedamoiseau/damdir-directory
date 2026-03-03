# WordPress.org Deployment Guide (All Purpose Directory)

This guide is the operational runbook to publish **All Purpose Directory** to WordPress.org with minimal risk.

---

## 0) Current status snapshot (as of latest check)

- `README.txt` stable tag: **1.0.0** ✅
- Plugin header version (`all-purpose-directory.php`): **1.0.0** ✅
- `CHANGELOG.md`: `## [1.0.0] - Unreleased` ⚠️
- Git tags: **none yet** (expected before release) ⚠️
- Working tree: local-only changes present (`bin/sync-to-test.sh`, `docs/WPORG-PREFLIGHT.md`) — acceptable if excluded from release package

Environment blockers detected on this host:
- `composer` not installed
- `php` not installed
- `playwright` not installed (no runnable `npm run test:e2e`)

---

## 1) Preflight (Go/No-Go)

Before any release action, all MUST be true:

- [ ] Security checks passed (REST/auth/capability/sanitize-escape final pass)
- [ ] Quality gates passed (lint + phpcs + unit + integration + e2e)
- [ ] Metadata consistency verified (`README.txt`, plugin header, changelog)
- [ ] Clean release package validated on fresh WordPress install
- [ ] Final release commit pushed to `main`

If any item fails: **NO-GO**.

---

## 2) Prepare release commit

1. Ensure release branch state (typically `main`):

```bash
git checkout main
git pull --ff-only
```

2. Confirm release metadata:
- `README.txt`: `Stable tag: 1.0.0`
- `all-purpose-directory.php`: `Version: 1.0.0`
- `CHANGELOG.md`: mark 1.0.0 as released (replace `Unreleased` with date)

3. Commit release metadata updates:

```bash
git add README.txt all-purpose-directory.php CHANGELOG.md
git commit -m "chore(release): finalize 1.0.0 metadata"
git push origin main
```

---

## 3) Run quality gates

> Run in an environment that has PHP/Composer/Node/Playwright available.

```bash
composer lint
composer phpcs
composer test:unit
composer test:integration
npm ci
npx playwright install --with-deps
npm run test:e2e
```

Record outputs in release notes/checklist.

---

## 4) Build release package

Use repo root and respect `.distignore`:

```bash
# Example using git archive (or your existing packaging script)
git archive --format=zip --output all-purpose-directory-1.0.0.zip HEAD
```

Then verify package manually:
- no `.git`, `.claude*`, `.idea`, local scripts, test-only artifacts
- required plugin files present

---

## 5) Fresh install smoke test (mandatory)

On a clean WP instance:

1. Install zip
2. Activate plugin
3. Basic feature smoke:
   - listing creation/edit/view
   - search/filter/pagination
   - frontend submission flow
4. Deactivate/reactivate
5. Uninstall smoke (no fatal errors)

---

## 6) Git tag (after all checks pass)

```bash
git tag -a 1.0.0 -m "Release 1.0.0"
git push origin 1.0.0
```

---

## 7) WordPress.org publish flow

### If first submission (not approved yet)
1. Submit via: <https://wordpress.org/plugins/developers/add/>
2. Wait for plugin review + assigned SVN slug
3. Address review feedback quickly

### After approval (SVN available)

```bash
svn co https://plugins.svn.wordpress.org/<slug> wporg-<slug>
```

- Copy plugin release files into `trunk/`
- Commit trunk
- Copy trunk to `tags/1.0.0`
- Commit tag

Example:

```bash
cd wporg-<slug>
# sync files to trunk/
svn add --force trunk/* --auto-props --parents --depth infinity -q
svn ci -m "Release 1.0.0"

svn cp trunk tags/1.0.0
svn ci -m "Tag 1.0.0"
```

---

## 8) Post-publish verification

- Verify wp.org listing page is updated
- Verify version install/update works from WP admin
- Verify plugin readme/changelog rendering on wp.org

---

## 9) Rollback strategy

If critical issue is found post-release:
1. Fix on `main`
2. Cut hotfix tag (e.g. `1.0.1`)
3. Publish new SVN tag
4. Document incident and mitigation in changelog

---

## 10) Operational notes for this repo

- `bin/sync-to-test.sh` is host-specific and should stay local-only.
- `docs/WPORG-PREFLIGHT.md` and this runbook can remain as internal docs.
- Do not create git tag until quality/security/package gates are green.
