# DataCorePoint

**Agriculture Information System** for organizing farmer, farm, crop, planting, and harvest records. Built with PHP and Supabase PostgreSQL.

> Current release: **v2.5**

## Features

- Dashboard summaries and operational charts
- Farmer, farm, crop, planting, and harvest management
- Reference-table dropdowns for normalized values such as crop categories, soil types, irrigation methods, quality grades, and harvest remarks
- Search, reports, archive and restore workflows
- Account roles, notifications, and administrator activity history
- PostgreSQL constraints and foreign-key relationships managed in Supabase

## Run locally

Requirements: PHP 8.1+ with `pdo_pgsql`, Git, and a Supabase PostgreSQL project.

1. Clone this repository and open the `agrinavarro` folder.
2. Configure `includes/db.local.php` using the example at `includes/db.local.php.example`, or supply the `SUPABASE_DB_*` environment variables listed in `agrinavarro/.env.example`.
3. Start PHP’s local server from the `agrinavarro` folder:

   ```powershell
   php -S 127.0.0.1:8094 -t .
   ```

4. Open <http://127.0.0.1:8094/login.php>.

Keep the terminal open while using the app; press **Ctrl+C** to stop the server. Do not expose PHP’s development server directly to the public internet.

## Configuration and security

Database credentials belong in the ignored local config or the hosting provider’s private environment settings. Never commit `.env`, `includes/db.local.php`, database passwords, access tokens, session files, or real personal data. The committed `.env.example` contains placeholders only. Require SSL for Supabase connections.

The app requires PHP and `pdo_pgsql`. A Dockerfile is included for Koyeb deployment; see the [deployment guide](agrinavarro/docs/DEPLOYMENT.md) for service settings and required secrets.

## Project guide

Application source and project documentation are in [`agrinavarro/`](agrinavarro/README.md). Useful references:

- [Setup and local LAN demo](agrinavarro/README.md)
- [Project documentation](agrinavarro/PROJECT_DOCUMENTATION.md)
- [ERD and relationships](agrinavarro/docs/ERD.md)
- [Normalization review](agrinavarro/docs/NORMALIZATION_REVIEW.md)
- [Data dictionary](agrinavarro/docs/DATA_DICTIONARY.md)
- [Verification record and remaining checks](agrinavarro/docs/TEST_RESULTS.md)
- [Versioning, GitHub, and rollback guide](agrinavarro/docs/VERSIONING.md)
- [Supabase migrations](agrinavarro/supabase/migrations/)
- [Deployment and migration notes](agrinavarro/docs/DEPLOYMENT.md)

Before a demonstration, complete the browser checks listed in the verification record and capture screenshots with personal information removed.
