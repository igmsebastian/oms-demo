# Basty Order Management

Basty Order Management is a Laravel 13, Inertia v3, React 19 order management system for sneaker inventory, order workflows, reports, refunds, cancellations, and product reference data.

For the complete setup walkthrough, read [setup-instructions.md](setup-instructions.md).

## Start Here

Use this quick path when setting up the project for the first time:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Then configure `.env`, create the database, and seed sample data:

```bash
php artisan migrate:fresh --seed
npm run build
```

Start the queue worker when testing emails, low-stock alerts, and after-commit jobs:

```bash
php artisan queue:work
```

## Environment Configuration

Copy `.env.example` to `.env`, then review these values:

| Key                | Local Default            | Notes                                                                |
| ------------------ | ------------------------ | -------------------------------------------------------------------- |
| `APP_NAME`         | `Basty Order Management` | Used in page titles, mail, logs, and UI metadata.                    |
| `APP_URL`          | `http://localhost`       | Use your local URL, such as `https://oms-demo.test` when using Herd. |
| `DB_CONNECTION`    | `sqlite`                 | SQLite is fastest for local setup. MySQL works too.                  |
| `CACHE_STORE`      | `database`               | Use `redis` on servers that have Redis configured.                   |
| `QUEUE_CONNECTION` | `database`               | Required for queued order emails and low-stock alerts.               |
| `MAIL_MAILER`      | `log`                    | Keeps local email safe by writing to logs.                           |
| `VITE_APP_NAME`    | `${APP_NAME}`            | Frontend app title fallback.                                         |

After changing `.env`, clear cached config if needed:

```bash
php artisan config:clear
```

## Database Setup

### SQLite

SQLite is the default in `.env.example`.

Create the database file:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item -ItemType File -Force database/database.sqlite
```

Then run migrations:

```bash
php artisan migrate
```

### MySQL

If you prefer MySQL, update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oms_demo
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL, then run:

```bash
php artisan migrate
```

## Sample Data

Seeders create:

| Seeder               | What It Adds                                                                            |
| -------------------- | --------------------------------------------------------------------------------------- |
| `OrderStatusSeeder`  | Integer-backed OMS statuses.                                                            |
| `AdminUserSeeder`    | Admin users, including `basty@mydemo.com`.                                              |
| `CustomerUserSeeder` | Customer accounts and shipping addresses.                                               |
| `ProductSeeder`      | Sneaker products, brands, categories, sizes, colors, tags, and stock states.            |
| `DemoOrderSeeder`    | Historical and recent orders with logs, inventory movement, cancellations, and refunds. |

Reset and seed everything:

```bash
php artisan migrate:fresh --seed
```

Seed only order statuses:

```bash
php artisan db:seed --class=OrderStatusSeeder
```

Demo admin login:

```text
Email: basty@mydemo.com
Password: password
```

## Running The App

If you use Laravel Herd, open your configured local site, for example:

```text
https://oms-demo.test
```

Run Vite for frontend changes:

```bash
npm run dev
```

If you are not using Herd or Valet, use the project dev script:

```bash
composer run dev
```

That starts Laravel, the queue listener, and Vite together.

## Useful Commands

| Command                                  | Purpose                                |
| ---------------------------------------- | -------------------------------------- |
| `php artisan migrate:fresh --seed`       | Rebuild database with sample OMS data. |
| `php artisan queue:work`                 | Process queued mail and jobs.          |
| `php artisan schedule:run`               | Run scheduled cleanup tasks.           |
| `php artisan route:list --except-vendor` | Inspect app routes.                    |
| `php artisan test --compact`             | Run the test suite.                    |
| `vendor/bin/pint --dirty --format agent` | Format changed PHP files.              |
| `npm run lint`                           | Run and fix frontend lint issues.      |
| `npm run build`                          | Build frontend assets.                 |

## Architecture

The backend follows:

```text
Controller -> Service -> Repository
```

| Layer         | Responsibility                                                                                     |
| ------------- | -------------------------------------------------------------------------------------------------- |
| Controllers   | HTTP, Inertia/API response orchestration, authorization, redirects.                                |
| Form Requests | Validation and request authorization.                                                              |
| Services      | Business rules, transactions, status changes, inventory changes, cache invalidation, side effects. |
| Repositories  | Eloquent queries, filters, pagination, report aggregates.                                          |
| Models/Enums  | Relationships, casts, accessors, statuses, constants.                                              |

The frontend follows Feature-Sliced Design:

```text
shared -> entities -> features -> widgets -> pages
```

## More Documentation

| File                                           | Purpose                                                       |
| ---------------------------------------------- | ------------------------------------------------------------- |
| [setup-instructions.md](setup-instructions.md) | Full local setup guide.                                       |
| [docs/order-process.md](docs/order-process.md) | Order status, cancellation, refund, and stock movement rules. |
| [docs/coreBackend.md](docs/coreBackend.md)     | Backend architecture and developer guide.                     |
| [docs/coreFrontend.md](docs/coreFrontend.md)   | Frontend architecture and UI guide.                           |
| [docs/deployment.md](docs/deployment.md)       | Deployment checklist and Forge notes.                         |

## Troubleshooting

| Problem                       | Fix                                                     |
| ----------------------------- | ------------------------------------------------------- |
| App key missing               | Run `php artisan key:generate`.                         |
| SQLite database missing       | Create `database/database.sqlite`, then run migrations. |
| Tables missing                | Run `php artisan migrate:fresh --seed`.                 |
| Frontend change not visible   | Run `npm run dev` or `npm run build`.                   |
| Queued email/job not running  | Start `php artisan queue:work`.                         |
| Config value looks stale      | Run `php artisan config:clear`.                         |
| Report/chart data looks stale | Run `php artisan cache:clear`, then reseed if needed.   |
