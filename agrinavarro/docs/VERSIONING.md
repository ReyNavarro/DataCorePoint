# Version and rollback workflow

Current application release: **v2.5**. The footer reads `APP_VERSION` from `includes/version.php`.

## Publish an update

The Git repository is the parent `Midterm Project` folder; application source lives in `agrinavarro/`. From the repository root:

```powershell
git pull --ff-only
git status
```

For this release, the application version and README labels are set to v2.5. For the next update, increment `APP_VERSION` and both README labels to 2.6. Review and push:

```powershell
git add README.md agrinavarro
git diff --cached --name-only
git diff --cached
git diff --cached --check
git commit -m "Release DataCorePoint v2.5"
git tag -a v2.5 -m "DataCorePoint v2.5"
git push origin main
git push origin v2.5
```

The commands above publish the prepared v2.5 release. Replace the version and tag in all three commands for future updates. Check that local credentials remain untracked:

```powershell
git check-ignore agrinavarro/includes/db.local.php agrinavarro/.env
git ls-files agrinavarro/includes/db.local.php agrinavarro/.env
```

The first command should list the ignored files; the second should print nothing. Never use `git add -f` on local credentials. If credentials were ever committed, rotate them because a later deletion does not remove them from Git history.

## Roll back

To inspect a release without changing the active branch:

```powershell
git switch --detach v2.5
```

Return with `git switch main`. To undo a later change on `main` while preserving history, use `git revert <commit-id>`, increase the app version, then commit, tag, and push the revert. Avoid `git reset --hard` for routine rollback because it discards local work.

## Hosting

The current app requires PHP 8.3 and `pdo_pgsql` in the supplied Koyeb Docker image. Follow [DEPLOYMENT.md](DEPLOYMENT.md) for service settings. Keep database credentials in Koyeb Secrets, never in GitHub.
