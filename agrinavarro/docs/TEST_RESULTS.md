# Verification record

Updated 2026-11-12. This file separates checks already run from work that still needs human review or live write testing.

## Dashboard presentation and release check (2026-11-12)

- Set the visible application release to v2.1 and documented the version/tag and rollback workflow in [VERSIONING.md](VERSIONING.md).
- Added subtle entrance and count-up motion to dashboard content, with reduced-motion preferences respected. Added keyboard focus support to expand the icon sidebar.
- All 45 PHP files passed `php -l`; `git diff --check` passed. These are syntax and whitespace checks, not browser-based visual or end-to-end verification of the new animations.
- The local `includes/db.local.php` and `.env` files are ignored and not tracked. No GitHub push or hosting deployment has been performed.

## Login and LAN demo recheck

- Supabase `SELECT 1` succeeded from the configured PHP runtime.
- Login GET returned HTTP 200 on both `127.0.0.1:8094` and `<LAN-IP>:8094`.
- A deliberately invalid login reached the database and returned the expected invalid-credentials message without a PHP stack trace.
- All 44 PHP files passed `php -l` after the latest session and login changes.
- The PHP server is bound to `0.0.0.0:8094`. The Windows Firewall rule could not be created from this non-administrator session; follow the README steps in Administrator PowerShell before asking another device to connect.

## Logout and activity history

- The confirmation and thank-you logout views rendered with their expected buttons and messages.
- All project PHP files passed syntax lint after the logout and activity-log changes.
- A read-only Supabase query confirmed the activity-log lookup and search query can execute.
- Activity records are created for sign-in/out, account management, and successful create, update, archive, and restore actions. No live data-changing action was submitted during this check.
- Edit entries include the record ID and changed field names; private field values such as contact details and passwords are not copied into the audit log.

## Completed checks

- PHP syntax lint completed for application files and local database configuration.
- A read-only query verified that the local configuration connects to the Supabase PostgreSQL project.
- Authenticated, read-only page requests rendered the dashboard, reports, harvests, user management, and archived records without PHP warnings.
- Farmer, farm, crop, planting, and harvest live-search endpoints returned table markup without PHP warnings.
- The database catalog was inspected for tables, columns, nullability, defaults, primary keys, unique constraints, and foreign keys. See [DATA_DICTIONARY.md](DATA_DICTIONARY.md).
- A read-only data audit found no missing required lookup IDs, no disagreement between existing labels and lookup rows, and no negative quantities or invalid planting date ranges in the current records.
- The ERD image in [ERD.md](ERD.md) came from the supplied schema diagram. Editable Mermaid relationships are included there as well.
- The public source tree excludes local database configuration, environment files, and preview session data.

## Login, session, and source safety recheck

- The login page and stylesheet returned HTTP 200 from the local PHP server. The loader is now positioned relative to the login card.
- Login responses set `no-store` caching and `nosniff`; login and all protected POST forms use CSRF tokens.
- All 44 PHP files pass `php -l`. `git diff --check` passes. The source scan previously found no POST forms without a CSRF field.
- `includes/db.local.php` and `.env` are ignored and are not present in the local Git history.
- The logout completion page contains a ten-second countdown and automatic return to sign-in. The completed logout flow still needs a browser check.

## Not yet completed

- The lookup normalization migration and the 2026-11-06 sequence, farmer-date-default, and unread-notification migrations are applied to the connected Supabase project. A read-only audit confirmed zero ID sequences behind their table maximums, the farmer registration default is `CURRENT_DATE`, and the notification watermark column exists.
- The reported live create failures were traced to sequences behind existing IDs and repaired. The planting delete handler was empty and has been implemented as a CSRF-protected archive action. Re-test create, edit, archive, restore, and permanent delete in the browser; no disposable record CRUD was run during this fix.
- Screenshots of the visible modules have not been captured. Remove real farmer/contact details before saving screenshots in a public repository. The current live data dictionary is dated 2026-11-05 and must be regenerated after the schema migration before submission.
- The `origin` remote now points to `https://github.com/ReyNavarro/DataCorePoint.git`; the current local changes have not been committed or pushed.
- Rotate the database password if it was ever exposed outside the private local config, and update only the ignored local configuration.

## Suggested final demonstration checks

1. Sign in and show the dashboard after its loading indicator completes.
2. Add and edit one disposable farmer, farm, crop, planting, and harvest. Confirm dropdown labels are visible and matching integer IDs are stored in their foreign key fields.
3. Check live search, date-range reports, archive, and restore behavior.
4. Try invalid IDs and negative numeric values; confirm validation prevents saving.
5. Remove or anonymize test records and screenshots before publishing.
