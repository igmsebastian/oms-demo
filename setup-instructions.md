# Setup Instructions

This guide explains how to set up Basty Order Management locally, configure the environment, prepare the database, and load sample OMS data.

## Requirements

| Tool     | Version / Notes                                                     |
| -------- | ------------------------------------------------------------------- |
| PHP      | 8.4 recommended. Composer allows `^8.3`.                            |
| Composer | Required for Laravel dependencies.                                  |
| Node.js  | Use a recent LTS version compatible with Vite 8.                    |
| npm      | Used by this project for scripts.                                   |
| Database | SQLite for local setup, or MySQL if preferred.                      |
| Queue    | Database queue locally; Redis is recommended on production servers. |

## 1. Install Dependencies

```bash
composer install
npm install
```

## 2. Create The Environment File

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

## 3. Configure `.env`

Start with these values:

```env
APP_NAME="Basty Order Management"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://oms-demo.test

DB_CONNECTION=sqlite

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@mydemo.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Use your actual local URL for `APP_URL`. If you use Laravel Herd, the URL is usually based on the project folder, such as:

```text
https://oms-demo.test
```

If you change `.env` after config has been cached, run:

```bash
php artisan config:clear
```

## 4. Database Setup

### Option A: SQLite

SQLite is the simplest local database.

Create the database file:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item -ItemType File -Force database/database.sqlite
```

Make sure `.env` has:

```env
DB_CONNECTION=sqlite
```

Then migrate:

```bash
php artisan migrate
```

### Option B: MySQL

Create a database, for example `oms_demo`, then update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oms_demo
DB_USERNAME=root
DB_PASSWORD=
```

Then migrate:

```bash
php artisan migrate
```

## 5. Load Sample Data

For a complete local reset with sample data:

```bash
php artisan migrate:fresh --seed
```

This runs:

| Seeder               | Data Created                                                                                             |
| -------------------- | -------------------------------------------------------------------------------------------------------- |
| `OrderStatusSeeder`  | Pending, confirmed, processing, shipped, cancelled, refunded, and other OMS statuses.                    |
| `AdminUserSeeder`    | Admin users for operations.                                                                              |
| `CustomerUserSeeder` | Customer users and addresses.                                                                            |
| `ProductSeeder`      | Sneaker catalog, product management references, tags, and stock quantities.                              |
| `DemoOrderSeeder`    | Historical and current orders, order activities, inventory logs, cancellations, refunds, and chart data. |

Demo admin account:

```text
Email: basty@mydemo.com
Password: password
```

Sample customer accounts also use:

```text
Password: password
```

## 6. Build Or Run Frontend Assets

For development:

```bash
npm run dev
```

For production-style compiled assets:

```bash
npm run build
```

If you use Herd, keep Herd serving Laravel and run only Vite with `npm run dev`.

If you are not using Herd or Valet, this script starts Laravel, the queue listener, and Vite:

```bash
composer run dev
```

## 7. Run Queues

Queues are used for order emails, refund/cancellation notifications, and low-stock alerts.

```bash
php artisan queue:work
```

For local email testing, `MAIL_MAILER=log` writes email content to Laravel logs instead of sending real mail.

## 8. Verify The Setup

Run these checks:

```bash
php artisan route:list --except-vendor
npm run build
php artisan test --compact
```

For faster checks while developing:

```bash
vendor/bin/pint --dirty --format agent
npm run lint
```

## Common Tasks

| Task                           | Command                                         |
| ------------------------------ | ----------------------------------------------- |
| Reset database and sample data | `php artisan migrate:fresh --seed`              |
| Seed only order statuses       | `php artisan db:seed --class=OrderStatusSeeder` |
| Clear config cache             | `php artisan config:clear`                      |
| Clear app cache                | `php artisan cache:clear`                       |
| Process queue jobs             | `php artisan queue:work`                        |
| Run scheduled tasks            | `php artisan schedule:run`                      |
| Build assets                   | `npm run build`                                 |

## Troubleshooting

| Problem                          | What To Check                                                                     |
| -------------------------------- | --------------------------------------------------------------------------------- |
| Login does not work              | Run `php artisan migrate:fresh --seed`, then use `basty@mydemo.com` / `password`. |
| `APP_KEY` error                  | Run `php artisan key:generate`.                                                   |
| SQLite database error            | Confirm `database/database.sqlite` exists.                                        |
| Missing tables                   | Run `php artisan migrate`.                                                        |
| No sample products/orders        | Run `php artisan migrate:fresh --seed`.                                           |
| Frontend assets missing          | Run `npm run build` or `npm run dev`.                                             |
| Toasts or UI changes not visible | Refresh after Vite rebuild; clear browser cache if needed.                        |
| Queued email not appearing       | Run `php artisan queue:work` and check `storage/logs`.                            |
| Config not changing              | Run `php artisan config:clear`.                                                   |

## Next Reading

| File                                           | What It Covers                                     |
| ---------------------------------------------- | -------------------------------------------------- |
| [README.md](README.md)                         | Project overview and quick setup.                  |
| [docs/order-process.md](docs/order-process.md) | OMS status, refund, cancellation, and stock rules. |
| [docs/coreBackend.md](docs/coreBackend.md)     | Backend architecture and developer workflow.       |
| [docs/coreFrontend.md](docs/coreFrontend.md)   | Frontend architecture and UI conventions.          |
| [docs/deployment.md](docs/deployment.md)       | Deployment process and checklist.                  |
