# Survos website deployment

The public site is the `showcase` Dokku app on fsn1, backed by `showcase-db` PostgreSQL.
The Git remote is `dokku@fsn1:showcase`; `git push dokku main` runs the build and migrations.
APP_SECRET and DATABASE_URL belong in Dokku config, never Git.

## Two inventories

- Local `/catalog`, `/apps`, `/tools`: disk-based inventory from the existing `app:load` scanner of sibling composer.json files. These routes are blocked in production.
- Public `/`: reviewed portfolio snapshot. `portfolio:sync` runs on an operator workstation with SSH and GitHub CLI access. It checks actual Dokku domains and running state, combines GitHub metadata, and emits only explicitly selected public apps into `config/public-portfolio.json`. Private repositories do not get public source links. Harvest is not selected.
- After reviewing the snapshot, commit it. `portfolio:load` idempotently loads its small set of components; it runs on release so subsequent snapshots are reflected in PostgreSQL. Production needs no SSH or GitHub credentials.

Do not run the local disk scanner in production. Add public projects explicitly in PublicPortfolio's PUBLIC_APPS list, then refresh the snapshot. Medium is https://medium.com/@tacman1123; Substack is deferred until a URL exists.

Before every push run `php bin/console doctrine:schema:validate` against local PostgreSQL. Verify `/health`, the database-backed homepage, compiled CSS, and denial of `/catalog` and `/api/components` after deployment.

### Canonical hostname

Dokku's persistent `/home/dokku/showcase/nginx.conf.d/canonical-host.conf`
contains the versioned `ops/canonical-host.conf`. It redirects both HTTP and
HTTPS `www.survos.com` requests to `https://survos.com`, preserving the URI.
After installing it, validate nginx and reload. The Let's Encrypt certificate
covers both hostnames; Dokku's automatic renewal cron job is enabled.
