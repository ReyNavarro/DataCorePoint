# DataCorePoint

**Agriculture Information System** for organizing farmer, farm, crop, planting, and harvest records. Built with PHP and Supabase PostgreSQL.

> Current app version: **v2.5.1** (not yet a GitHub Release)

> **School project only:** This project is for educational and demonstration purposes, not production use. Use test data only.

Release notes and the difference between tagged releases and the current app version are in [CHANGELOG.md](CHANGELOG.md). GitHub currently has releases v2.1 and v2.2. Create a GitHub Release for each finished version after committing and pushing it; v2.5.1 is prepared locally and is not published yet. See the [release workflow](agrinavarro/docs/VERSIONING.md).

## Features

- Dashboard summaries and operational charts
- Farmer, farm, crop, planting, and harvest management
- Reference-table dropdowns for normalized values such as crop categories, soil types, irrigation methods, quality grades, and harvest remarks
- Search, reports, archive and restore workflows
- Account roles, notifications, and administrator activity history
- PostgreSQL constraints and foreign-key relationships managed in Supabase

## Run locally

Requirements: XAMPP (PHP 8.1+ with `pdo_pgsql` enabled), Git, and a Supabase PostgreSQL project.

1. Configure `agrinavarro/includes/db.local.php` using `agrinavarro/includes/db.local.php.example`, or supply the `SUPABASE_DB_*` environment variables listed in `agrinavarro/.env.example`.
2. In PowerShell, run the PHP server from the repository root:

   ```powershell
   Set-Location .\agrinavarro
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8094 -t .
   ```

3. Open <http://127.0.0.1:8094/login.php>.

Keep the terminal open while using the app; press **Ctrl+C** to stop the server. If XAMPP is installed somewhere other than `C:\xampp`, change the executable path. Do not expose PHP’s development server directly to the public internet.

## Try the website

- **On this computer:** after starting the local server, open <http://127.0.0.1:8094/login.php>.
- **Public Render site:** no live Render URL is available yet. After deployment, open the service in the Render Dashboard, use the service URL shown there, and add `/login.php` to the end of it.

## Configuration and security

Database credentials belong in the ignored local config or the hosting provider’s private environment settings. Never commit `.env`, `includes/db.local.php`, database passwords, access tokens, session files, or real personal data. The committed `.env.example` contains placeholders only. Require SSL for Supabase connections.

The app requires PHP and `pdo_pgsql`. A Dockerfile is included for Render deployment; see the [deployment guide](agrinavarro/docs/DEPLOYMENT.md) for service settings and required secrets.

## Project guide

Application source and project documentation are in [`agrinavarro/`](agrinavarro/README.md). Useful references:

- [Setup and local testing](agrinavarro/README.md)
- [Release notes](CHANGELOG.md)
- [Project documentation](agrinavarro/PROJECT_DOCUMENTATION.md)
- [ERD and relationships](agrinavarro/docs/ERD.md)
- [Normalization review](agrinavarro/docs/NORMALIZATION_REVIEW.md)
- [Data dictionary](agrinavarro/docs/DATA_DICTIONARY.md)
- [Verification record and remaining checks](agrinavarro/docs/TEST_RESULTS.md)
- [Versioning, GitHub, and rollback guide](agrinavarro/docs/VERSIONING.md)
- [Supabase migrations](agrinavarro/supabase/migrations/)
- [Deployment and migration notes](agrinavarro/docs/DEPLOYMENT.md)

Before a demonstration, complete the browser checks listed in the verification record and capture screenshots with personal information removed.
