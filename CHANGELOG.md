# DataCorePoint release notes

DataCorePoint is a school project for demonstrating agricultural record management. Use a test database and non-sensitive data; this project is not intended for production use.

These notes distinguish GitHub releases (version tags) from the version currently shown by the application. Only **v2.1** and **v2.2** currently have Git tags. The application displays **v2.5**, but v2.3, v2.4, and v2.5 do not have Git tags and are not published GitHub releases.

## v2.2 — tagged release

**Updated**

- Changed the application version label from v2.1 to v2.2.
- Updated the root and application README version references and version/rollback instructions.
- Added the unused bundled Start Bootstrap template folder to `.gitignore`.

**Features:** No new application features were added in this release.

## v2.1 — tagged release

First tagged version of the DataCorePoint school project.

**Added**

- PHP and Supabase PostgreSQL application for managing farmers, farms, crops, plantings, and harvests.
- Sign-in, role-based admin pages, dashboard summaries, search, and reports.
- Archive and restore workflows, notifications, and administrator activity history.
- Database reference tables, schema documentation, and setup instructions.

## v2.3 — no GitHub release found

There is no v2.3 Git tag or tracked release commit in this repository's history, so a separate list of v2.3 changes cannot be verified. Do not treat the version label or local snapshot as a published GitHub release.

## v2.4 — no GitHub release found

There is no v2.4 Git tag or tracked release commit in this repository's history, so a separate list of v2.4 changes cannot be verified. Do not treat the version label or local snapshot as a published GitHub release.

## v2.5 — current app label, not tagged

The application version was updated to v2.5 in the code, but no v2.5 Git tag or GitHub release exists yet.

**Updated**

- Refreshed dashboard colors, card presentation, loading animation, and sidebar interactions.
- Improved harvest sorting and kept table/live-search controls consistent.
- Replaced the archive permanent-delete browser prompt with an in-app confirmation and added checks for active child records.
- Updated the Docker/Apache deployment configuration and documentation. The later Render setup uses port 10000 and PHP 8.3 with `pdo_pgsql`.
- Updated local XAMPP instructions and formatted shared/login page markup.

**Added**

- A DataCorePoint favicon for browser tabs.

**Verification status:** XAMPP PHP 8.2.12 passed syntax checks for all 45 application PHP files; the local login page and favicon returned HTTP 200; and a read-only database connection check succeeded. Authenticated CRUD workflows have not been fully retested, and the Docker image and Render deployment have not been verified. A public Render test URL is not available until the service is deployed.
