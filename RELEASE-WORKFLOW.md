# WP Agency Admin Toolkit Pro Release Workflow

This repository includes a GitHub Actions workflow that automatically builds the customer install ZIP when a version tag is pushed.

## Required repository structure

```text
.github/workflows/release.yml
wp-agency-admin-toolkit-pro/
  wp-agency-admin-toolkit-pro.php
  readme.txt
  includes/
  assets/
```

## How to release a new version

1. Update the plugin version in `wp-agency-admin-toolkit-pro/wp-agency-admin-toolkit-pro.php`.
2. Update `AAT_VERSION` in the same file.
3. Update `Stable tag` and the changelog in `wp-agency-admin-toolkit-pro/readme.txt`.
4. Commit and push the changes.
5. Create and push a matching tag, for example:

```bash
git tag v1.22
git push origin v1.22
```

The workflow will build and attach this release asset:

```text
wp-agency-admin-toolkit-pro.zip
```

The ZIP will contain the correct WordPress plugin folder:

```text
wp-agency-admin-toolkit-pro/
```

## Important

The workflow checks that the GitHub tag version matches the plugin header version. For example, tag `v1.22` requires `Version: 1.22` in the main plugin file.
