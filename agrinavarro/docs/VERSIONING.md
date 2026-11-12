# Versioning and rollback

The footer gets its version from `includes/version.php`. The project currently displays **v2.5**, while the GitHub repository has released tags **v2.1** and **v2.2**. Do not call v2.5 a published release until its changes have been reviewed, tested, committed, and tagged. See the [release notes](../../CHANGELOG.md).

## Prepare a release

From the repository root, review the complete change and make sure no credentials or private data are included:

```powershell
git status --short
git diff --check
git diff
git diff --cached
git check-ignore agrinavarro\includes\db.local.php agrinavarro\.env
git ls-files agrinavarro\includes\db.local.php agrinavarro\.env
```

After local and browser testing succeeds, update `APP_VERSION` in `agrinavarro/includes/version.php`, the version label in the root and application READMEs, and the release notes. Stage only the intended release files, inspect them, then commit and tag:

```powershell
git add <reviewed-files>
git diff --cached --check
git diff --cached
git commit -m "Release DataCorePoint v2.6"
git tag -a v2.6 -m "DataCorePoint v2.6"
git push origin main
git push origin v2.6
```

Replace `2.6` with the next version only after confirming the prior release. The ignore check should list local configuration files, while `git ls-files` should print nothing. Never force-add credentials. If credentials were committed in the past, rotate them; deleting them later does not remove them from Git history.

## Roll back

Inspect a tagged release without changing the active branch:

```powershell
git switch --detach v2.2
```

Return to the working branch with `git switch main`. To undo a change already on the branch while preserving history, use `git revert <commit-id>`, test the result, and commit and push the revert. Avoid `git reset --hard` for routine rollback because it discards local work.

## Hosting

The supplied Render Docker image uses PHP 8.3, Apache, and `pdo_pgsql`. See [the Render deployment guide](DEPLOYMENT.md). No Render deployment has been completed from this workspace, so a public test URL is not yet available.
