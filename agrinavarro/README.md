# DataCorePoint Agriculture Information System

A PHP and Supabase PostgreSQL application for managing farmers, farms, crops, planting records, and harvests.

Current application version: **v2.5**. This is a school project for educational and demonstration purposes, not production use; use test data only. See the [release notes](../CHANGELOG.md) for tagged versions and current development status, and [versioning and rollback](docs/VERSIONING.md) before releasing or reverting a change.

## Requirements

- PHP 8.1 or later with `pdo_pgsql` enabled
- A Supabase PostgreSQL project
- Git for version control

## Run locally on port 8094

Requirements: XAMPP with PHP 8.1 or later and the `pdo_pgsql` extension enabled, plus a configured Supabase PostgreSQL connection.

Open PowerShell in the repository root and run:

```powershell
Set-Location .\agrinavarro
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8094 -t .
```

Then open [http://127.0.0.1:8094/login.php](http://127.0.0.1:8094/login.php). Keep that PowerShell window open while using the site; press Ctrl+C to stop it.

This is the local website address to use for testing. No public Render URL is available until the service is deployed; after deployment, find its URL in the Render Dashboard and append `/login.php`.

If XAMPP is installed in another folder, update the path to `php.exe`. You can also use `php -S 127.0.0.1:8094 -t .` if PHP is already on your `PATH`.

The app needs a local database config at `includes/db.local.php`, or these environment variables:

```text
SUPABASE_DB_HOST
SUPABASE_DB_PORT
SUPABASE_DB_NAME
SUPABASE_DB_USER
SUPABASE_DB_PASSWORD
SUPABASE_DB_SSLMODE
APP_HTTPS
```

`includes/db.local.php` and real `.env` files are excluded from Git. Never use `git add -f` on them. `.env.example` contains placeholders only. The database connection requires SSL (`require` by default). Set `APP_HTTPS=1` when the production site uses HTTPS, including when TLS ends at a trusted reverse proxy. Set secrets through the host's private configuration panel.

A Render Docker deployment path is included. See [deployment and database notes](docs/DEPLOYMENT.md) for setup steps and required secrets.

## Documentation and deliverables

- [Project documentation](PROJECT_DOCUMENTATION.md)
- [ERD and lookup relationships](docs/ERD.md)
- [Live schema data dictionary](docs/DATA_DICTIONARY.md)
- [Normalization review](docs/NORMALIZATION_REVIEW.md)
- [Normalized lookup migration for review](supabase/migrations/20261105_normalize_lookup_fields.sql)
- [Verification record](docs/TEST_RESULTS.md)
- [Deployment and migration notes](docs/DEPLOYMENT.md)
- [Versioning and rollback](docs/VERSIONING.md)
- [Release notes](../CHANGELOG.md)
- [Environment variable template](.env.example)
- Keep screenshots and completed test evidence in a dedicated `docs/evidence/` folder after removing real personal data.

The lookup normalization migration has been applied to the connected Supabase project. Follow-up migrations add the automatic farmer registration-date default, repair sequences that were behind imported IDs, and add unread-notification tracking. Remaining pre-submission work is end-to-end browser testing and privacy-safe screenshots; see [TEST_RESULTS.md](docs/TEST_RESULTS.md).

The SQL migration files are kept in the repository for review and database reproducibility. The application does not run them automatically. Archived rows stay in their original tables under the `is_deleted` flag; the admin Archived page reads those rows and supports restoration and permanent deletion. Test these flows against disposable records before submitting.
