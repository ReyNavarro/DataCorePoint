# Version and rollback workflow

Current release: **v2.1**. The application footer reads the version from `includes/version.php`.

## For each future update

1. Finish and review the change locally. Run PHP syntax checks and test the affected pages.
2. Increase `APP_VERSION` in `includes/version.php`; for example, change `2.1` to `2.2`.
3. Check that local secrets and private test data are not tracked:

   ```powershell
   git status --short
   git check-ignore includes/db.local.php .env
   git ls-files includes/db.local.php .env
   git diff --check
   ```

   `git ls-files` should print nothing for those secret files. If it prints a secrets file, stop and untrack it before pushing. If a credential was ever committed, rotate it; deleting the file in a later commit does not remove it from Git history.

4. Review the staged files, commit, tag, and push that release. Replace `2.2` below with the version in `includes/version.php`:

   ```powershell
   git add -A
   git diff --cached --name-only
   git diff --cached
   git commit -m "Release v2.2"
   git tag -a v2.2 -m "DataCorePoint v2.2"
   git push origin main
   git push origin v2.2
   ```

## Revert to an earlier version

To inspect an old release without changing the current branch:

```powershell
git switch --detach v2.1
```

Return to the active branch with `git switch main`. To undo a later release on `main` while preserving history, run `git revert v2.2`, then increase the version, commit, tag, and push the revert. Replace `v2.2` with the release you are undoing. Avoid `git reset --hard` for ordinary rollback because it discards local work.

## First push to the empty GitHub repository

Open PowerShell in the `agrinavarro` folder. Review the files and confirm the secret checks pass before pushing:

```powershell
git status --short
git check-ignore includes/db.local.php .env
git ls-files includes/db.local.php .env
git diff --check
git add -A
git diff --cached --name-only
git diff --cached
git branch -M main
git commit -m "Release v2.1"
git tag -a v2.1 -m "DataCorePoint v2.1"
git push -u origin main
git push origin v2.1
```

If the remote is not actually empty, stop before the first push and reconcile its history. Authenticate through Git Credential Manager or the browser; never paste a GitHub token into a file, command, screenshot, or chat.

## Hosting note

GitHub can store this source code, but GitHub Pages and Netlify static hosting cannot execute these PHP request handlers. Netlify documents PHP as a build dependency; this project needs PHP to process web requests. Use a host that runs PHP 8.1+ with `pdo_pgsql` and supports private environment variables. Keep the Supabase database password and other secrets in that host's private settings, not in GitHub.
