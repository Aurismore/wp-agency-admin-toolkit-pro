# WP Agency Admin Toolkit Pro Release Workflow

This repository ships a GitHub Actions workflow that builds the customer install ZIP when a version tag is pushed.

## Repository layout

```text
.github/workflows/release.yml
wp-agency-admin-toolkit-pro.php   # plugin bootstrap (root of the repo)
readme.txt
uninstall.php
composer.json
includes/aliases.php              # back-compat AAT_* class aliases
src/                              # namespaced classes (Aurismore\AAT\*)
assets/
```

The plugin source lives at the repository root. The release workflow stages the
files into a `wp-agency-admin-toolkit-pro/` folder at build time so the
delivered ZIP unpacks into the correct WordPress plugin directory.

## How to release a new version

1. Update the plugin version in `wp-agency-admin-toolkit-pro.php` (both the
   `Version:` header and the `AAT_VERSION` constant).
2. Update `Stable tag` and the changelog block in `readme.txt`.
3. Commit and push the changes to `main`.
4. Create and push a matching tag, for example:

```bash
git tag v1.22
git push origin v1.22
```

The workflow builds and attaches this release asset:

```text
wp-agency-admin-toolkit-pro.zip
```

The ZIP contains the correct WordPress plugin folder:

```text
wp-agency-admin-toolkit-pro/
```

## Tag verification

The workflow refuses to publish a release unless **all four** of these match:

- The tag (`vX.Y` → `X.Y`)
- The plugin header `Version:` field
- The `AAT_VERSION` constant
- The `Stable tag:` line in `readme.txt`

This prevents shipping a ZIP that disagrees with itself about which version it is.
