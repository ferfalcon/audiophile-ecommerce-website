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

Do not import production customer data or commit the generated `wp-config.php`, database, or uploads.

## WooCommerce local setup and test fixtures

WooCommerce is required for the storefront templates and mini-cart block, but the plugin is not vendored or deployed by this repository. Install the tested version in DDEV for local work; production plugin installation is a separate operation from the theme workflow. From the WordPress project directory:

```sh
ddev start
ddev wp plugin install woocommerce --version=11.2.1 --activate
ddev wp plugin get woocommerce --field=version
ddev wp plugin list
```

Activation creates the Shop, Cart, Checkout, and My Account pages with WooCommerce-managed page content. If they are missing on a fresh local database, use **WooCommerce → Status → Tools → Create default WooCommerce pages** or the WooCommerce page setup flow. Keep the Cart and Checkout blocks in their assigned page content; the theme's `page-cart.html` and `page-checkout.html` templates render that content through `core/post-content`.

Use WordPress **Settings → Permalinks** to enable pretty permalinks. The current theme navigation uses WooCommerce's default category routes (`/product-category/headphones/`, `/product-category/speakers/`, and `/product-category/earphones/`); products use `/product/<slug>/`, with `/shop/`, `/cart/`, and `/checkout/` provided by WooCommerce.

The current DDEV database contains six fictional test products in the three matching categories. Their demo media is in ignored local uploads. A local-only US flat-rate method and Cash on Delivery gateway exercise shipping totals and checkout. On the user's explicit request, the same fictional product details and demo images were separately added to production; local test orders, customer/address data, and local shipping/payment settings were not copied. No live payment gateway or carrier is configured.

Useful local checks:

```sh
ddev wp wc product list --user=1
ddev wp term list product_cat --fields=term_id,name,slug,count
ddev wp option get woocommerce_cart_page_id
ddev wp option get woocommerce_checkout_page_id
```

## Git and production release flow

1. Create a feature branch and implement/test locally in DDEV.
2. Push the branch and open a pull request to `main`.
3. Review and merge the PR. The production workflow validates `theme.json` before syncing the theme.
4. The production workflow deploys only `wp-content/themes/audiophile/` over SSH/rsync to:
   `/home/u933167856/domains/audiophile-e-commerce-website.ferfalcon.shop/public_html/wp-content/themes/audiophile/`
5. Run a post-deploy HTTP smoke check and retain the previous commit as a rollback target.

The GitHub Actions workflow is at `.github/workflows/deploy-production.yml`. It runs on changes to the theme or workflow on `main`, and supports manual dispatch from `main`. It validates `theme.json`, then synchronizes only the theme directory (with deletion of stale files scoped to that directory).

The repository has been configured with a dedicated Hostinger SSH deployment key as the `HOSTINGER_SSH_KEY` secret and these Actions variables: `HOSTINGER_HOST`, `HOSTINGER_USER`, `HOSTINGER_PORT`, `HOSTINGER_THEME_PATH`, and `HOSTINGER_KNOWN_HOSTS`. The server key was pinned after confirming its fingerprint against the existing trusted SSH host key. The deployment key disables TTY and forwarding. The first GitHub Actions deployment completed successfully (commit `d6d5a4a`). The Audiophile theme was subsequently activated on production with WP-CLI; verification confirmed it is the active theme and the live site responds with HTTP 200. Consider enabling required reviewers for the GitHub `production` environment and branch protection on `main`.

Deployments must not synchronize WordPress core, `wp-config.php`, plugins, uploads, or database state. Routine theme releases must not run database migrations or WordPress core/plugin updates. The Audiophile theme has been reviewed and activated on production; subsequent theme releases update its tracked files only.

## Current production state

- PR [#1](https://github.com/ferfalcon/audiophile-ecommerce-website/pull/1) implemented the responsive header/footer and local Manrope font. PR [#2](https://github.com/ferfalcon/audiophile-ecommerce-website/pull/2) changed the header/footer wordmark to editable linked text so it remains “audiophile” even when the WordPress Site Title option contains the domain.
- PRs [#1](https://github.com/ferfalcon/audiophile-ecommerce-website/pull/1), [#2](https://github.com/ferfalcon/audiophile-ecommerce-website/pull/2), and [#3](https://github.com/ferfalcon/audiophile-ecommerce-website/pull/3) are merged to `main`. Deployment run [38956260574](https://github.com/ferfalcon/audiophile-ecommerce-website/actions/runs/38956260574) completed successfully; the current main merge is `df1e2f9`.
- Theme stylesheet `0.5.3` is active in production. Post-deploy smoke checks passed for category and product pages, Cart, and Checkout at desktop and mobile widths; no horizontal overflow or missing images was found after lazy-loaded images finished loading.
- WooCommerce `11.2.1` was installed and activated on production via WP-CLI after taking a verified database and site-files backup outside the document root at `/home/u933167856/backups/audiophile/20261016T192658Z/`. The archive contains 7,040 entries; the SQL dump contains 21 table definitions. Keep this backup through the rollback window. For ongoing backups, use Hostinger hPanel scheduled backups and create an on-demand full backup before production changes.
- Production now has the three fictional categories and six fictional products with their demo images. WooCommerce's coming-soon setting is off, so the catalog is public. No local test orders or customer/address data were copied. WooCommerce created/assigned Cart and Checkout pages. No payment gateways are enabled, and no production shipping or tax configuration was added; checkout cannot complete a live order. No production order was placed during verification.
- The WordPress Site Title setting on production still contains the domain; the theme wordmark no longer depends on that setting. The homepage content remains the existing “Hello world!” starter content and was intentionally not changed.

## Verification and remaining limitations

- DDEV browser checks covered 375px, 768px, and 1440px widths, keyboard menu open/Escape close, and visible focus. No horizontal overflow was found; the mobile-menu axe audit had zero violations. The desktop audit flags the existing homepage’s missing level-one heading, outside this header/footer change.
- The repository has no official social profile URLs or approved commercial product media. The current production catalog intentionally uses fictional demo content and images at the user's request; replace them with approved catalog content before treating the store as a real shop. Category archives use WooCommerce's default `/product-category/<slug>/` routes.
- WooCommerce 11.2.1 is active in local DDEV and production. The theme supplies WooCommerce block templates for product archives, single products, Cart, Checkout, and order confirmation; the header mini-cart, gallery, add-to-cart, cart, checkout, and order confirmation use WooCommerce blocks.
- Local browser verification covered 375px, 1280px, and 1440px layouts, category and product views, add-to-cart, mini-cart quantity changes, cart quantity/totals, required checkout field validation, local flat-rate shipping, offline Cash on Delivery, and order confirmation. The completed test order and its fictional customer/address are local database data only.
- The workflow remains unchanged and deploys only the theme; it does not install/update plugins or sync uploads, products, orders, or database state. Production WooCommerce and the fictional catalog were provisioned separately after explicit approval. Plugin ownership/update cadence, staging/rollback operations, approved commercial catalog/media, tax, payment, shipping, privacy/terms, transactional email, and order operations remain production decisions. Do not enable live gateways or carrier integrations without explicit approval.
