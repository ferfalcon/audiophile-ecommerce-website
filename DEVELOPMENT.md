# Development and deployment workflow

## Repository layout

- `wp-content/themes/audiophile/` is the deployable custom block theme.
- `.ddev/` contains local development configuration. It is committed so contributors share the same setup.
- WordPress core, `wp-config.php`, the local database, production data, and uploads are not source-controlled or part of the theme deployment.

## Start a local site with DDEV

Prerequisites: Docker and DDEV. From the repository root:

```sh
ddev start
ddev wp core download
ddev wp core install \
  --url=https://audiophile-ecommerce-website.ddev.site \
  --title="Audiophile" \
  --admin_user=admin \
  --admin_password='change-this-local-password' \
  --admin_email=you@example.test
```

DDEV provides a local `wp-config.php` automatically. Use only disposable local credentials. The local site is then available at <https://audiophile-ecommerce-website.ddev.site>; sign in at `/wp-admin/`. The Audiophile theme is activated in the initial local install; to activate it again later, run `ddev wp theme activate audiophile`.

Useful commands:

```sh
ddev describe
ddev wp theme list
ddev stop
```

Do not import production customer data or commit the generated `wp-config.php`, database, or uploads. If a local content fixture/database is needed later, create a sanitized source and document its import process.

## Git and production release flow

1. Create a feature branch and implement/test locally in DDEV.
2. Push the branch and open a pull request to `main`.
3. Review and merge the PR. GitHub Actions should run theme validation/build checks before allowing release.
4. A future production workflow will deploy only `wp-content/themes/audiophile/` over SSH/rsync to:
   `/home/u933167856/domains/audiophile-e-commerce-website.ferfalcon.shop/public_html/wp-content/themes/audiophile/`
5. Run a post-deploy HTTP smoke check and retain the previous commit as a rollback target.

The GitHub Actions workflow is at `.github/workflows/deploy-production.yml`. It runs on changes to the theme or workflow on `main`, and supports manual dispatch from `main`. It validates `theme.json`, then synchronizes only the theme directory (with deletion of stale files scoped to that directory).

The repository has been configured with a dedicated Hostinger SSH deployment key as the `HOSTINGER_SSH_KEY` secret and these Actions variables: `HOSTINGER_HOST`, `HOSTINGER_USER`, `HOSTINGER_PORT`, `HOSTINGER_THEME_PATH`, and `HOSTINGER_KNOWN_HOSTS`. The server key was pinned after confirming its fingerprint against the existing trusted SSH host key. The deployment key disables TTY and forwarding. The first GitHub Actions deployment completed successfully (commit `d6d5a4a`). The Audiophile theme was subsequently activated on production with WP-CLI; verification confirmed it is the active theme and the live site responds with HTTP 200. Consider enabling required reviewers for the GitHub `production` environment and branch protection on `main`.

Deployments must not synchronize WordPress core, `wp-config.php`, plugins, uploads, or database state. Routine theme releases must not run database migrations or WordPress core/plugin updates. Activate the theme once on production after reviewing it; subsequent releases update its tracked files only.
