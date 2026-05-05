# Deployment Guide

## 🚀 Overview

This project is currently intended for Laravel Forge deployment. The steps below are written for Forge first, but they are general enough for manual deployment on a Laravel-capable server.

Core runtime pieces:

- Laravel app server
- database
- queue worker
- scheduler
- frontend build assets in `public/build`
- mail transport
- cache/session store

## ✅ Pre-Deployment Checklist

- [ ] code committed
- [ ] tests passing
- [ ] frontend build passes
- [ ] `.env` prepared
- [ ] database credentials ready
- [ ] queue configured
- [ ] cache/session/mail configured
- [ ] storage linked
- [ ] migrations reviewed
- [ ] seeders reviewed
- [ ] scheduler configured
- [ ] backup taken if production

## 🧪 Local Verification Before Deploy

Run these before deploying:

```bash
composer install
npm install
npm run build
php artisan test --compact
vendor/bin/pint --dirty
php artisan route:list --except-vendor
```

If a command fails locally, fix it before pushing to the server.

## ⚙️ Environment Configuration

Important `.env` keys:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=
QUEUE_CONNECTION=
SESSION_DRIVER=
MAIL_MAILER=
```

Rules:

- Never commit `.env`.
- Production should use `APP_DEBUG=false`.
- Keep mail, queue, cache, and session settings aligned with the server services.
- If using Redis, confirm Redis is installed and reachable before switching `CACHE_STORE`, `QUEUE_CONNECTION`, or `SESSION_DRIVER`.

## 🧱 Deployment Steps

General Laravel deployment commands:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan queue:restart
```

Notes:

- Use `npm ci` on servers when `package-lock.json` is present.
- Run `php artisan storage:link` once per server or whenever the symlink is missing.
- Use `php artisan migrate --force` only after reviewing migrations.

## 🔥 Laravel Forge Notes

Forge can pull from GitHub, run deployment scripts, manage environment variables, configure SSL/domains, run queue daemons, and schedule commands.

Configure in Forge:

- site repository
- deployment script
- `.env`
- worker daemon for queues
- scheduler cron
- SSL/domain

Suggested Forge deploy script:

```bash
cd /home/forge/your-site.com

git pull origin main

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci
npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
```

If your project path or branch differs, update `cd /home/forge/your-site.com` and `git pull origin main`.

## 📬 Queue Worker

The queue is required for:

- order emails
- low stock alerts
- other after-commit jobs

Commands:

```bash
php artisan queue:work
php artisan queue:restart
```

Forge:

- add a daemon/process for `php artisan queue:work`
- restart the worker after deployment with `php artisan queue:restart`

Suggested worker command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

## ⏱️ Scheduler

The scheduler is required for:

- `orders:cleanup-statuses`
- automatic delivered-to-completed cleanup

Cron:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Forge:

- add a scheduled job for `php artisan schedule:run`

The command registration is in:

```text
routes/console.php
```

## 🧹 Cache and Optimization

Optimization commands:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

Clear cache when:

- config changes
- route changes
- deployment uses stale values
- report/dashboard data appears stale
- troubleshooting strange behavior

Useful reset:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Then rebuild caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🧯 Deployment Troubleshooting

| Issue | Fix |
| --- | --- |
| 500 after deploy | Check logs, `APP_DEBUG` temporarily in staging only |
| CSS/JS missing | Run `npm run build`, check `public/build` |
| Migrations failed | Check DB credentials and migration SQL |
| Emails not sending | Check mail env, queue worker, `storage/logs/mailing.log` |
| Jobs not running | Check queue worker/daemon |
| Scheduler not running | Check cron/Forge scheduler |
| Routes missing | Run `route:clear` then `route:cache` |
| Config stale | Run `config:clear` then `config:cache` |

Primary logs:

| Log | Purpose |
| --- | --- |
| `storage/logs/laravel.log` | application errors |
| `storage/logs/mailing.log` | queued mail and delivery failures |

## 📦 Rollback Notes

Rollback options:

- revert Git commit
- redeploy a previous commit
- database rollback only if safe
- restore from backup when data shape changed significantly

Migration rollback command:

```bash
php artisan migrate:rollback --step=1
```

Warning: use rollback carefully in production. Never blindly rollback production migrations without a backup and a clear understanding of data loss risk.

## ✅ Post-Deployment Checklist

- [ ] site loads
- [ ] login works
- [ ] dashboard loads
- [ ] orders page loads
- [ ] products page loads
- [ ] report export works
- [ ] queue worker running
- [ ] scheduler running
- [ ] emails tested
- [ ] logs checked
# Deployment Guide

## 🚀 Overview

This project is currently intended for Laravel Forge deployment. The steps below are also useful for manual Laravel deployments on a VPS or similar server.

The production deployment must cover PHP dependencies, frontend assets, database migrations, queues, scheduler, cache optimization, storage linking, and environment configuration.

## ✅ Pre-Deployment Checklist

- [ ] code committed
- [ ] tests passing
- [ ] frontend build passes
- [ ] `.env` prepared
- [ ] database credentials ready
- [ ] queue configured
- [ ] cache/session/mail configured
- [ ] storage linked
- [ ] migrations reviewed
- [ ] seeders reviewed
- [ ] scheduler configured
- [ ] backup taken if production

## 🧪 Local Verification Before Deploy

```bash
composer install
npm install
npm run build
php artisan test --compact
vendor/bin/pint --dirty
php artisan route:list --except-vendor
```

Run these locally or in CI before deploying. A production deploy should not be the first place build or test failures are discovered.

## ⚙️ Environment Configuration

Important `.env` keys:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=
QUEUE_CONNECTION=
SESSION_DRIVER=
MAIL_MAILER=
```

Never commit `.env`.

Production should use `APP_DEBUG=false`. Enable debug only temporarily in a staging environment when investigating a deployment issue.

Recommended production backing services:

| Concern | Recommendation |
|---|---|
| Cache | Redis |
| Queue | Redis or database queue, with a running worker |
| Session | Redis or database |
| Mail | A real SMTP/API mail provider |
| Files | Linked storage via `php artisan storage:link` |

## 🧱 Deployment Steps

General Laravel deployment commands:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan queue:restart
```

Run migrations after the new code is available and before cache warmup where possible.

## 🔥 Laravel Forge Notes

Forge can pull from GitHub and run a deployment script on every deploy.

Configure in Forge:

- deployment script
- environment variables
- queue worker daemon
- scheduler cron
- SSL/domain
- PHP version
- database connection

Suggested Forge deploy script:

```bash
cd /home/forge/your-site.com

git pull origin main

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci
npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
```

If the site uses a different branch, update `git pull origin main` to match the deployment branch.

## 📬 Queue Worker

A queue worker is required for:

- order emails
- low stock alerts
- other after-commit jobs

Commands:

```bash
php artisan queue:work
php artisan queue:restart
```

Forge:

- Add a daemon/process for the queue worker.
- Restart the worker after deployment.
- Monitor failed jobs and logs after deploy.

## ⏱️ Scheduler

The scheduler is required for:

- `orders:cleanup-statuses`
- automatic delivered-to-completed cleanup

Cron:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Forge:

- Add a scheduled job for `php artisan schedule:run`.
- Confirm it runs every minute.

## 🧹 Cache and Optimization

Useful cache and optimization commands:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

When to clear cache:

- config changes
- route changes
- strange stale behavior
- report data appears stale after mutation

After clearing config or route cache in production, rebuild the cache with `config:cache` and `route:cache`.

## 🧯 Deployment Troubleshooting

| Issue | Fix |
|---|---|
| 500 after deploy | Check logs, `APP_DEBUG` temporarily in staging only |
| CSS/JS missing | Run `npm run build`, check `public/build` |
| Migrations failed | Check DB credentials and migration SQL |
| Emails not sending | Check mail env and queue worker |
| Jobs not running | Check queue worker/daemon |
| Scheduler not running | Check cron/Forge scheduler |
| Routes missing | Run `route:clear` then `route:cache` |
| Config stale | Run `config:clear` then `config:cache` |

Useful log paths:

| Log | Path |
|---|---|
| Laravel app log | `storage/logs/laravel.log` |
| Mailing log | `storage/logs/mailing.log` |
| Web server logs | Forge server logs / Nginx logs |

## 📦 Rollback Notes

Rollback process:

- Revert the Git commit or redeploy a previous commit.
- Confirm frontend assets match the deployed backend commit.
- Roll back database only if the migration is safe to reverse.
- Never blindly roll back production migrations without a backup.

Migration rollback command:

```bash
php artisan migrate:rollback --step=1
```

Use rollback carefully in production. Prefer a forward fix when a migration has already changed important production data.

## ✅ Post-Deployment Checklist

- [ ] site loads
- [ ] login works
- [ ] dashboard loads
- [ ] orders page loads
- [ ] products page loads
- [ ] report export works
- [ ] queue worker running
- [ ] scheduler running
- [ ] emails tested
- [ ] logs checked
