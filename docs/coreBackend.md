# Backend Core Documentation

## 🧭 Start Here

When working on backend features, begin at the HTTP entry point and follow the request down into the business layer. Do not start by editing a model or controller until you know which service owns the rule.

Recommended flow:

1. Check the route/controller.
2. Check request validation.
3. Check the service.
4. Check the repository.
5. Check the model/migration.
6. Check or add tests.

Common starting commands:

```bash
php artisan route:list --except-vendor
php artisan test --compact tests/Feature/Oms
```

## 🧱 Architecture Overview

The backend follows a Controller -> Service -> Repository structure.

| Layer | Responsibility | Example Path |
| --- | --- | --- |
| Controller | HTTP, Inertia, and API orchestration only | `app/Http/Controllers` |
| Request | Validation and authorization | `app/Http/Requests` |
| Service | Business rules, transactions, cache invalidation, side effects | `app/Services` |
| Repository | Query building, filters, pagination, eager loading | `app/Repositories` |
| Model | Relationships, casts, accessors | `app/Models` |
| Enum | Statuses and business constants | `app/Enums` |

Guidelines:

- Controllers should stay thin.
- Services own business logic.
- Repositories own queries.
- API and Inertia controllers must reuse the same services.
- Avoid duplicating business logic in controllers.

## 🗂️ Important Backend Paths

| Concern | Path |
| --- | --- |
| Admin Controllers | `app/Http/Controllers/Admin` |
| API Controllers | `app/Http/Controllers/Api` |
| Form Requests | `app/Http/Requests` |
| Services | `app/Services` |
| Repositories | `app/Repositories` |
| Repository Contracts | `app/Contracts/Repositories` |
| Models | `app/Models` |
| Enums | `app/Enums` |
| Filters | `app/Filters` |
| Policies | `app/Policies` |
| Mail | `app/Mail` |
| Jobs | `app/Jobs` |
| Config | `config` |
| Migrations | `database/migrations` |
| Seeders | `database/seeders` |
| Tests | `tests/Feature/Oms` |

## 🚦 Order Management Flow

The order lifecycle is the main business workflow in the system.

| Step | What Happens | Main Backend Files |
| --- | --- | --- |
| Order creation | Customer creates an order, product/address snapshots are stored, order starts as pending | `OrderService`, `StoreOrderRequest`, `OrderObserver` |
| Fulfillment / processing | Admin confirms or fulfills an order and moves it through allowed statuses | `OrderService`, `OrderStatusTransitionService` |
| Inventory deduction | Stock is deducted when an order is confirmed/fulfilled | `InventoryService`, `InventoryLogRepository` |
| Cancellation | Customer can request cancellation; admin can cancel eligible orders | `OrderCancellationService` |
| Partial cancellation | Admin cancels part of an item quantity and restores eligible stock | `OrderCancellationService` |
| Refund | Customer/admin refund flow records refund state and optionally restores stock | `OrderRefundService` |
| Activity logging | User/system/order events are written to activity logs | `OrderActivityService` |
| Inventory logging | Every stock mutation writes stock before/after and context | `InventoryService`, `inventory_logs` |

Detailed business rules live in [order-process.md](order-process.md).

## 🔌 API Development Guide

API controllers live in `app/Http/Controllers/Api`. Admin/Inertia controllers live in `app/Http/Controllers/Admin`. Both must call the same service methods so API and UI behavior remain consistent.

API responses should use resources from `app/Http/Resources`. Inertia responses pass props to pages under `resources/js/pages`.

Example API flow:

```php
public function index(OrderFilter $filter): AnonymousResourceCollection
{
    return OrderResource::collection(
        $this->orders->paginate($filter)
    );
}
```

When adding a backend API feature, update these pieces:

| Need | Where |
| --- | --- |
| New API route | `routes/api.php` |
| Web/Inertia route | `routes/web.php` |
| Request validation | `app/Http/Requests` |
| JSON shape | `app/Http/Resources` |
| Business behavior | `app/Services` |
| Query behavior | `app/Repositories` and `app/Filters` |
| Authorization | `app/Policies` |
| Tests | `tests/Feature/Oms` |

## 🧪 Validation Guide

Form Requests validate backend input. Zod validates frontend input. Backend validation is always authoritative, even when the frontend has client-side validation.

| Action | Request Class |
| --- | --- |
| Create Product | `StoreProductRequest` |
| Update Product | `UpdateProductRequest` |
| Create Order | `StoreOrderRequest` |
| Cancel Order | `CancelOrderRequest` |
| Partial Cancel Item | `PartialCancelOrderItemRequest` |
| Update Order Status | `UpdateOrderStatusRequest` |
| Refund Order | `StoreRefundRequest` |

Validation rules should produce clear, user-readable messages. Do not rely on database errors as user feedback.

## 🧾 Query Filters

Filters extend the reusable query filter pattern in `app/Filters`. They support request-driven filtering and sorting for backend-paginated tables.

Supported request patterns:

- `filters[...]`
- `sorts[...]`
- `per_page`
- `s`

Example:

```http
GET /api/orders?filters[status]=2&filters[keyword]=ORD&sorts[created_at]=desc&per_page=15
```

Filter classes:

- `ProductFilter`
- `OrderFilter`
- `InventoryLogFilter`
- `OrderActivityFilter`
- `UserFilter`

Important: filtering must happen in the backend query, not only on the current frontend table page.

## 🧮 Caching

Reports and list payloads are cached to reduce repeated database work. Cache invalidation is part of the service layer because services know when business data changes.

Known report cache keys:

- `reports.orders.summary`
- `reports.revenue.summary`
- `reports.inventory.status`
- `reports.inventory.low_stock_count`

Cache invalidation happens after:

- product create/update/delete
- stock adjustment
- order fulfillment
- cancellation
- partial cancellation
- refund completion
- scheduler cleanup

Useful command:

```bash
php artisan cache:clear
```

Cache-related logic usually lives in:

- `ReportService`
- `InventoryService`
- `ProductService`
- `OrderService`
- `OrderCancellationService`
- `OrderRefundService`
- `OmsCacheService`

## 🚧 Rate Limiting

Rate limiting protects write-heavy actions from spam and accidental repeated submissions. Backend routes and middleware are authoritative.

Important examples:

- order remarks/comments are rate limited
- report exports are rate limited
- sensitive actions use stricter throttles

Check these files:

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/Api/OrderController.php`
- `app/Providers/AppServiceProvider.php`

Example route style:

```php
Route::post('/orders/{order:order_number}/remarks', ...)
    ->middleware('throttle:order-remarks');
```

## 📬 Queues and Emails

Emails and side-effect jobs are queued. Order-related jobs use `afterCommit()` so they only run after a successful database transaction.

This matters because an email should not be sent if an order status transition rolls back.

Common commands:

```bash
php artisan queue:work
php artisan queue:restart
```

Important files:

| Concern | Path |
| --- | --- |
| Email config and recipient map | `config/order_emails.php` |
| Mail classes | `app/Mail` |
| Queued jobs | `app/Jobs` |
| Notification orchestration | `app/Services/OrderNotificationService.php` |
| Dedicated mail log | `storage/logs/mailing.log` |

Mail failures should not break order workflows. Failed queue dispatches or delivery failures are logged to the `mailing` channel.

## ⏱️ Scheduler

Scheduled cleanup is registered in `routes/console.php`.

Commands:

```bash
php artisan orders:cleanup-statuses
php artisan schedule:run
```

Rules:

- Delivered orders can auto-complete after the configured waiting period.
- Partially cancelled orders only become cancelled when all item quantities are cancelled.
- System activities are recorded.
- Inventory restoration is never duplicated.

Command implementation:

- `app/Console/Commands/CleanupOrderStatusesCommand.php`

## 🛠️ Artisan Commands

| Command | Purpose |
| --- | --- |
| `php artisan migrate` | Run migrations |
| `php artisan migrate:fresh --seed` | Reset database with seed data |
| `php artisan db:seed --class=OrderStatusSeeder` | Sync order statuses |
| `php artisan route:list --except-vendor` | Inspect routes |
| `php artisan test --compact` | Run tests |
| `vendor/bin/pint --dirty` | Format changed PHP files |
| `php artisan queue:work` | Process queued jobs |
| `php artisan schedule:run` | Run scheduled tasks |
| `php artisan cache:clear` | Clear cache |
| `php artisan config:clear` | Clear config cache |

## 🧯 Troubleshooting Guide

| Problem | Where to Check |
| --- | --- |
| API returns wrong data | Controller -> Resource -> Service -> Repository |
| Validation fails | Form Request |
| Order status is wrong | `OrderStatusTransitionService` and `config/order_status_transitions.php` |
| Inventory quantity is wrong | `InventoryService` and `inventory_logs` |
| Cache shows stale report | `ReportService` and cache invalidation |
| Email did not send | Queue worker, Mail class, `config/order_emails.php`, `storage/logs/mailing.log` |
| User cannot access page | Policy or middleware |
| Table filter not working | Filter class and repository |
| Order number missing | `OrderObserver` |

## 🧪 Testing Strategy

Feature tests prove business logic. A test that only checks HTTP 200 is weak. Important tests should assert database state, inventory quantities, status changes, activity logs, policies, cache behavior, queues, and validation errors.

Key test areas:

- inventory mutation
- order creation and fulfillment
- cancellation and partial cancellation
- refunds
- reports and cache invalidation
- policies and API/Inertia parity
- queues and email jobs

Commands:

```bash
php artisan test --compact
php artisan test --compact tests/Feature/Oms/OrderFulfillmentTest.php
```
# Backend Core Documentation

## 🧭 Start Here

When working on backend features, follow the request path from the outside in. This keeps controllers thin and makes sure business rules are implemented once.

Recommended flow:

1. Check the route/controller.
2. Check request validation.
3. Check the service.
4. Check the repository.
5. Check the model/migration.
6. Check or add tests.

For bugs, start with the endpoint that is failing, then follow the same flow until you reach the layer that owns the behavior.

## 🧱 Architecture Overview

The backend follows this structure:

```text
Controller -> Service -> Repository
```

| Layer | Responsibility | Example Path |
|---|---|---|
| Controller | HTTP/Inertia/API orchestration only | `app/Http/Controllers` |
| Request | Validation and authorization | `app/Http/Requests` |
| Service | Business rules, transactions, cache invalidation, side effects | `app/Services` |
| Repository | Query building, filters, pagination, eager loading | `app/Repositories` |
| Model | Relationships, casts, accessors | `app/Models` |
| Enum | Statuses and business constants | `app/Enums` |

Controllers should stay thin. They should authorize, validate through Form Requests, call a service or repository, and return an Inertia page, JSON Resource, or redirect.

Services own business logic. Put state transitions, transactions, inventory correctness, cache invalidation, activity logging, and queued side effects in services.

Repositories own queries. Put filters, pagination, eager loading, and reusable lookup methods in repositories.

API and Inertia controllers must reuse the same services. Avoid duplicating business logic in controllers because it creates API/Inertia behavior drift.

## 🗂️ Important Backend Paths

| Concern | Path |
|---|---|
| Admin Controllers | `app/Http/Controllers/Admin` |
| API Controllers | `app/Http/Controllers/Api` |
| Form Requests | `app/Http/Requests` |
| Services | `app/Services` |
| Repositories | `app/Repositories` |
| Repository Contracts | `app/Contracts/Repositories` |
| Models | `app/Models` |
| Enums | `app/Enums` |
| Filters | `app/Filters` |
| Policies | `app/Policies` |
| Mail | `app/Mail` |
| Jobs | `app/Jobs` |
| Config | `config` |
| Migrations | `database/migrations` |
| Seeders | `database/seeders` |
| Tests | `tests/Feature/Oms` |

## 🚦 Order Management Flow

The full order lifecycle is documented in [docs/order-process.md](order-process.md). Treat that file as the source of truth for business rules.

High-level flow:

| Step | Summary |
|---|---|
| Order creation | Creates a pending order, stores customer/address snapshots, calculates totals, and records an activity. |
| Fulfillment / processing | Moves eligible orders forward through the configured status transition flow. |
| Inventory deduction | Deducts product stock only at the fulfillment point and records inventory logs. |
| Cancellation | Supports customer cancellation requests and admin cancellation rules. |
| Partial cancellation | Cancels item quantities without destroying the historical order total. |
| Refund | Supports refund request, processing, completion, and optional stock restoration. |
| Activity logging | Records user/admin/system actions in the order activity feed. |
| Inventory logging | Records stock before/after, quantity change, actor, reason, and order context. |

## 🔌 API Development Guide

API controllers live in `app/Http/Controllers/Api`.

Admin/Inertia controllers live in `app/Http/Controllers/Admin`.

Both controller types must call the same service methods. API responses should use Resources, while Inertia responses should pass page props to React pages.

Example API flow:

```php
public function index(OrderFilter $filter): AnonymousResourceCollection
{
    return OrderResource::collection(
        $this->orders->paginate($filter)
    );
}
```

Where to add backend pieces:

| Need | Path |
|---|---|
| New API route | `routes/api.php` |
| New Inertia/admin route | `routes/web.php` |
| Request validation | `app/Http/Requests` |
| API resource | `app/Http/Resources` |
| Business action | `app/Services` |
| Query method | `app/Repositories` |
| Authorization | `app/Policies` |
| Feature test | `tests/Feature/Oms` |

## 🧪 Validation Guide

Form Requests validate backend input. Zod validates frontend input. Backend validation is always authoritative because browser validation can be bypassed.

| Action | Request Class |
|---|---|
| Create Product | `StoreProductRequest` |
| Update Product | `UpdateProductRequest` |
| Create Order | `StoreOrderRequest` |
| Cancel Order | `CancelOrderRequest` |
| Partial Cancel Item | `PartialCancelOrderItemRequest` |
| Update Order Status | `UpdateOrderStatusRequest` |
| Refund Order | `StoreRefundRequest` |

Validation messages should be friendly, direct, and useful to the user. Avoid exposing internal field names when a plain label is clearer.

## 🧾 Query Filters

Backend lists use filter classes so API and Inertia pages can share query behavior.

Common query parameters:

| Parameter | Purpose |
|---|---|
| `filters[...]` | Structured filters such as status, category, brand, date range, or stock state. |
| `sorts[...]` | Structured sorting such as `created_at=desc` or `price=asc`. |
| `per_page` | Backend pagination size. |
| `s` | Keyword search. |

Example:

```http
GET /api/orders?filters[status]=2&filters[keyword]=ORD&sorts[created_at]=desc&per_page=15
```

Filter classes:

- `ProductFilter`
- `OrderFilter`
- `InventoryLogFilter`
- `OrderActivityFilter`
- `UserFilter`

Filters must run in the backend query before pagination. Do not filter only the current page of results.

## 🧮 Caching

Report summaries are cached to reduce repeated database work for dashboard, reports, and inventory summary data.

Important cache keys:

- `reports.orders.summary`
- `reports.revenue.summary`
- `reports.inventory.status`
- `reports.inventory.low_stock_count`

The implementation may use date-aware or versioned variants of these keys, but the concepts above are the cache groups to keep in mind.

Cache invalidation happens after:

- product create/update/delete
- stock adjustment
- order fulfillment
- cancellation
- partial cancellation
- refund completion
- scheduler cleanup

Useful command:

```bash
php artisan cache:clear
```

Cache logic usually lives in:

- `app/Services/ReportService`
- `app/Services/InventoryService`
- `app/Services/ProductService`
- `app/Services/OrderService`
- `app/Services/OrderCancellationService`
- `app/Services/OrderRefundService`

## 🚧 Rate Limiting

Order remarks/comments are rate limited to prevent spam. Backend routes and middleware are authoritative even if the frontend disables repeated clicks.

Check these paths when changing rate limits:

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/Api/OrderController.php`

Example route:

```php
Route::post('/orders/{order:order_number}/remarks', ...)
    ->middleware('throttle:order-remarks');
```

## 📬 Queues and Emails

Emails and jobs are queued. Order-related side effects should use `afterCommit()` so emails are not sent when a database transaction fails.

Common commands:

```bash
php artisan queue:work
php artisan queue:restart
```

Configuration and code paths:

| Concern | Path |
|---|---|
| Order email mapping | `config/order_emails.php` |
| Mail classes | `app/Mail` |
| Jobs | `app/Jobs` |
| Mail failure logging | `storage/logs/mailing.log` |

Queue failures should be logged and must not break the core order operation after it has already succeeded.

## ⏱️ Scheduler

Scheduler commands support automatic maintenance tasks.

```bash
php artisan orders:cleanup-statuses
php artisan schedule:run
```

Current order cleanup behavior:

- Delivered orders can auto-complete after configured timing.
- Partially cancelled orders only become cancelled when all quantities are cancelled.
- System-generated activities are recorded.
- Inventory restoration is never duplicated.

The scheduler is registered in `routes/console.php`.

## 🛠️ Artisan Commands

| Command | Purpose |
|---|---|
| `php artisan migrate` | Run migrations |
| `php artisan migrate:fresh --seed` | Reset database with seed data |
| `php artisan db:seed --class=OrderStatusSeeder` | Sync order statuses |
| `php artisan route:list --except-vendor` | Inspect routes |
| `php artisan test --compact` | Run tests |
| `vendor/bin/pint --dirty` | Format changed PHP files |
| `php artisan queue:work` | Process queued jobs |
| `php artisan schedule:run` | Run scheduled tasks |
| `php artisan cache:clear` | Clear cache |
| `php artisan config:clear` | Clear config cache |

## 🧯 Troubleshooting Guide

| Problem | Where to Check |
|---|---|
| API returns wrong data | Controller → Resource → Service → Repository |
| Validation fails | Form Request |
| Order status is wrong | `OrderStatusTransitionService` and `config/order_status_transitions.php` |
| Inventory quantity is wrong | `InventoryService` and `inventory_logs` |
| Cache shows stale report | `ReportService` and cache invalidation |
| Email did not send | Queue worker, Mail class, `config/order_emails.php` |
| User cannot access page | Policy or middleware |
| Table filter not working | Filter class and repository |
| Order number missing | `OrderObserver` |

## 🧪 Testing Strategy

Feature tests prove business logic where database behavior matters. Important tests should cover inventory, orders, cancellations, refunds, reports, policies, cache invalidation, queues, and API/Inertia parity.

A test that only checks HTTP 200 is weak. Prefer assertions on database state, status changes, inventory quantities, activity logs, queued jobs/mail, validation errors, and authorization.

Run all tests:

```bash
php artisan test --compact
```

Run a focused test file:

```bash
php artisan test --compact tests/Feature/Oms/OrderFulfillmentTest.php
```
