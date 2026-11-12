# Render deployment

The repository root contains a Dockerfile for the PHP application. It installs PHP 8.3, Apache, and `pdo_pgsql`, copies the `agrinavarro/` application, and listens on port `10000`, Render's default web-service port. Local `.env` files, database config files, preview auth files, and session files are excluded from the image by `.dockerignore`.

## Create the Render service

1. Push the reviewed application commit to the GitHub branch you plan to deploy.
2. In Render, create a **Web Service** and connect the `ReyNavarro/DataCorePoint` repository.
3. Choose **Docker** as the runtime. Keep the repository root as the build context and use the root `Dockerfile`.
4. Configure the service's environment variables. Set the database password as a secret:

   - `SUPABASE_DB_HOST`: Supabase PostgreSQL host
   - `SUPABASE_DB_PORT`: `5432` or the port provided by your selected Supabase connection method
   - `SUPABASE_DB_NAME`: `postgres`
   - `SUPABASE_DB_USER`: database username from Supabase
   - `SUPABASE_DB_PASSWORD`: secret value; never commit it to GitHub
   - `SUPABASE_DB_SSLMODE`: `require`
   - `APP_HTTPS`: `1` for the HTTPS site

5. Keep the service's HTTP port at `10000`, matching the Docker image.
6. Deploy, review the build and runtime logs, then open the Render service domain and visit `/login.php`.

Render's [web-service guide](https://render.com/docs/web-services) describes service setup and port requirements. Its [Docker guide](https://render.com/docs/docker) explains how Render builds a Dockerfile from a connected repository.

## Before treating the deployment as ready

- Confirm the image builds, the service starts, and the site connects to Supabase.
- Confirm authentication works over HTTPS and `pdo_pgsql` is loaded in the container.
- Verify create, edit, archive, restore, permanent-delete, and report flows using disposable records.
- Do not use real personal data for public screenshots or a public demo unless you have permission and have secured the service appropriately.

Render terminates inbound TLS and forwards requests to the web service over HTTP. `APP_HTTPS=1` enables the application's secure-cookie behavior for the public HTTPS site. The Docker image and configuration are provided, but no Render deployment or production browser test has been run from this workspace.

## Supabase migrations and archive behavior

Keep the SQL files under `supabase/migrations/` in the GitHub repository. They document schema changes and help reviewers reproduce or audit the database. They are not application pages and are not automatically run during deployment or sign-in.

The migration files modify an existing application schema. For a fresh database, first create the base tables and seed required lookup rows, then review and apply migrations in date order in Supabase. Back up the database first. Do not re-run a migration against a database where it has already been applied unless its SQL is explicitly safe to re-run. Record the database and migration status in [TEST_RESULTS.md](TEST_RESULTS.md).

Archived farmers, farms, crops, plantings, and harvests remain in their original Supabase tables with `is_deleted = TRUE`. Normal lists exclude archived rows. The administrator's Archived page queries those tables, restores records by changing the flag to `FALSE`, and supports permanent deletion. Permanent deletion can be blocked while another record still references the item.
