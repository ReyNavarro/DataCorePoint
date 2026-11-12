# DataCorePoint Agriculture Information System

A PHP and Supabase PostgreSQL application for managing farmers, farms, crops, planting records, and harvests.

Current application version: **v2.1**. See [versioning and rollback](docs/VERSIONING.md) before releasing or reverting a change.

## Requirements

- PHP 8.1 or later with `pdo_pgsql` enabled
- A Supabase PostgreSQL project
- Git for version control

## Run locally on port 8094

Open PowerShell in this project folder and run:

```powershell
php -S 127.0.0.1:8094 -t .
```

Then open [http://127.0.0.1:8094/login.php](http://127.0.0.1:8094/login.php). Keep that PowerShell window open while using the site; press Ctrl+C to stop it.

### Let someone on your Wi-Fi test

Run the server so it listens on the computer's network interfaces:

```powershell
php -S 0.0.0.0:8094 -t .
```

Find the computer's IPv4 address with `ipconfig`, then give the tester `http://YOUR-IP:8094/login.php` . Replace `YOUR-IP` with the address printed on your computer. Both devices must be on the same private Wi-Fi. Do not port-forward this development server or expose it to the public internet. If Windows Firewall blocks the connection, run PowerShell as Administrator and add a rule limited to the private profile and local subnet:

```powershell
New-NetFirewallRule -DisplayName 'Agrinavarro local demo (PHP 8094)' -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8094 -Program 'C:\xampp\php\php.exe' -Profile Private -RemoteAddress LocalSubnet
```

The firewall rule requires an administrator account. Ask testers to use an account intended for testing; do not send them database credentials.

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

This project cannot be deployed to Netlify as a working PHP application: Netlify's PHP build support installs PHP for build commands, but the deployed app still needs a PHP request runtime for its `.php` routes. Use a PHP host with `pdo_pgsql`; see [the deployment and Git notes](docs/VERSIONING.md).

## Git and GitHub workflow

The `origin` remote is configured for `https://github.com/ReyNavarro/DataCorePoint.git`. Follow the complete first-push and safe-release steps in [VERSIONING.md](docs/VERSIONING.md). Review staged files carefully and confirm no secrets or private data are included before pushing.



The GitHub repository should be empty before the first push. If it already has a README, license, or other commit, stop before pushing and synchronize the histories first.

GitHub will ask you to authenticate through the browser or your configured Git Credential Manager. Do not paste an access token into source files, PowerShell commands, or chat.

Common commands:

```powershell
git status
git commit -m "Describe the change"
git push
git pull --ff-only
git log --oneline --decorate -10
```

Use `git status` before committing. Review staged files with `git diff --cached` and confirm local credentials remain ignored with:

```powershell
git check-ignore includes/db.local.php
git status --short
```

If you edit on two computers, run `git pull --ff-only` before starting work and push after committing. Do not commit local database configuration, `.env` files, session data, or real user data/screenshots.

## Documentation and deliverables

- [Project documentation](PROJECT_DOCUMENTATION.md)
- [ERD and lookup relationships](docs/ERD.md)
- [Live schema data dictionary](docs/DATA_DICTIONARY.md)
- [Normalization review](docs/NORMALIZATION_REVIEW.md)
- [Normalized lookup migration for review](supabase/migrations/20261105_normalize_lookup_fields.sql)
- [Verification record](docs/TEST_RESULTS.md)
- [Versioning and rollback](docs/VERSIONING.md)
- [Environment variable template](.env.example)
- Keep screenshots and completed test evidence in a dedicated `docs/evidence/` folder after removing real personal data.

The lookup normalization migration has been applied to the connected Supabase project. Follow-up migrations add the automatic farmer registration-date default, repair sequences that were behind imported IDs, and add unread-notification tracking. Remaining pre-submission work is end-to-end browser testing and privacy-safe screenshots; see [TEST_RESULTS.md](docs/TEST_RESULTS.md).
