# Order Management System Backend

## Architecture

This Laravel 13 application uses a Controller -> Service -> Repository shape for the OMS backend.

- Controllers handle HTTP, authorization, validation requests, redirects, and JSON resources.
- Services hold business rules, transactions, status changes, inventory mutations, cache invalidation, and side effects.
- Repositories hold Eloquent query construction, filtering, pagination, and report aggregates.
- API and Inertia controllers call the same services.
- ULIDs are used for application model primary keys. `order_statuses.id` remains an integer reference key.

## Status Flow

Order statuses are defined in `App\Enums\OrderStatus` and synced into `order_statuses` by `OrderStatusSeeder`.

Normal flow:

```text
pending -> confirmed -> processing -> packed -> shipped -> delivered -> completed
```

Cancellation flow:

```text
pending/confirmed/processing -> cancellation_requested -> cancelled
confirmed/processing -> partially_cancelled -> processing
```

Refund flow:

```text
cancelled/partially_cancelled -> refund_pending -> refunded
```

Allowed transitions are centralized in `config/order_status_transitions.php` and applied by `OrderStatusTransitionService`.

## Cancellation And Refunds

- Users can request cancellation for their own orders.
- Admins can approve full or partial cancellations.
- Confirmed inventory is restored when cancellation quantities are applied.
- `order_items.cancelled_quantity` prevents restoring the same quantity twice.
- Refunds are tracked in `order_refunds` and can move from pending to processing to completed.

## Queue Setup

Order and inventory emails are queued mailables. Dispatching uses after-commit behavior so emails and low-stock jobs are not queued until the surrounding transaction succeeds.

For local development:

```bash
php artisan queue:work
```

The email map is in `config/order_emails.php`.

## Cache Behavior

`ReportService` caches dashboard/reporting data with these keys:

- `reports.orders.summary`
- `reports.revenue.summary`
- `reports.inventory.status`
- `reports.inventory.low_stock_count`

The cache is invalidated after product stock changes, product updates/deletes, order confirmation, cancellation, and refund completion.

## Test Commands

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/ProductManagementTest.php
php artisan test --compact tests/Feature/OrderLifecycleTest.php
php artisan test --compact tests/Feature/ReportCacheTest.php
php artisan test --compact
```
