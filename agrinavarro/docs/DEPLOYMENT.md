# Deployment guide

## Koyeb deployment

The repository root contains a Dockerfile for this PHP application. It installs PHP 8.3, Apache, and `pdo_pgsql`, copies the `agrinavarro/` application, and serves it on port `8000`. Local `.env` files, database config files, preview auth files, and session files are excluded from the image by `.dockerignore`.

1. Push the reviewed application commit to the `main` branch on GitHub.
2. In Koyeb, create a **Web Service** and select **GitHub** as the source. Connect `ReyNavarro/DataCorePoint` and choose `main`.
3. Choose the **Dockerfile** builder. Keep the repository root as the build context and use the root `Dockerfile`.
4. Configure the exposed port as **8000**, protocol **HTTP**, and route **`/:8000`**. The app's sign-in page is `/login.php`.
5. Add the database settings in the service's environment configuration. Use Koyeb Secrets for the database password:

   - `SUPABASE_DB_HOST`: Supabase PostgreSQL host
   - `SUPABASE_DB_PORT`: `5432` or the port provided by your selected Supabase connection method
   - `SUPABASE_DB_NAME`: `postgres`
   - `SUPABASE_DB_USER`: database username from Supabase
   - `SUPABASE_DB_PASSWORD`: reference a Koyeb Secret; never paste it into GitHub
   - `SUPABASE_DB_SSLMODE`: `require`
   - `APP_HTTPS`: `1` (also set in the Docker image for secure session cookies behind Koyeb HTTPS)

6. Deploy, wait for the image build to finish, then open the Koyeb service domain and visit `/login.php`.
7. Review build and runtime logs if deployment fails. Check the database connection, login, dashboard, CRUD, archive/restore, and reports using disposable records before sharing the URL.

Koyeb's [PHP deployment guide](https://www.koyeb.com/docs/deploy/php) describes GitHub and Dockerfile deployments. Its [Git deployment guide](https://www.koyeb.com/docs/build-and-deploy/deploy-with-git) explains that new pushes can trigger builds. Store credentials with [Koyeb Secrets](https://www.koyeb.com/docs/reference/secrets).

## Before treating the deployment as ready

- Confirm the build succeeds and the site can connect to Supabase from Koyeb.
- Confirm authentication works over HTTPS and that `pdo_pgsql` loaded in the container.
- Verify each write, edit, delete-to-archive, restore, and permanent-delete action against disposable records. Check audit logs and reports afterward.
- Do not use real personal data for public screenshots or a public demo unless you have permission and have secured the service appropriately.

The Docker image and configuration are provided, but no Koyeb deployment or production browser test has been run from this workspace. A successful local PHP check does not prove the hosted database connection or every end-to-end workflow.

## Supabase migrations and archive behavior

Keep the SQL files under `supabase/migrations/` in the GitHub repository. They document schema changes and help reviewers reproduce or audit the database. They are not application pages and are not automatically run during deployment or sign-in.

The migration files modify an existing application schema. For a fresh database, first create the base tables and seed required lookup rows, then review and apply migrations in date order in Supabase. Back up the database first. Do not re-run a migration against a database where it has already been applied unless its SQL is explicitly safe to re-run. Record the database and migration status in [TEST_RESULTS.md](TEST_RESULTS.md).

Archived farmers, farms, crops, plantings, and harvests remain in their original Supabase tables with `is_deleted = TRUE`. Normal lists exclude archived rows. The administrator's Archived page queries those tables, restores records by changing the flag to `FALSE`, and supports permanent deletion. Permanent deletion can be blocked while another record still references the item.
