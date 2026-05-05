# Order Process

## Architecture

The OMS uses Controller -> Service -> Repository boundaries. Inertia and API controllers call the same services, services own business rules, and repositories own query construction. The frontend uses Feature-Sliced Design with this dependency direction:

shared -> entities -> features -> widgets -> pages

Higher layers may import lower layers. Lower layers must not import higher layers.

## Happy Flow

Pending -> Processing -> Packed -> Shipped -> Delivered -> Completed

Processing -> Shipped is an allowed shortcut when packing is skipped operationally.

Fulfill means an admin acknowledges the order and starts fulfillment. In this implementation, fulfillment confirms the order, deducts inventory, and moves the order into Processing.

## Cancellation Flow

Pending:

- Admin can cancel directly with a reason.
- A customer can request cancellation.

Processing:

- Admin can cancel or partially cancel with a reason.
- A customer can request cancellation.

Packed:

- Customer cancellation is no longer available.
- Admin cancellation depends on backend transition rules.

Shipped:

- Customer and admin cancellation are not available.

PartiallyCancelled means only some item quantities were cancelled. It does not represent a fully cancelled order.

CancellationRequested means the customer requested cancellation and an admin must review it.

## Refund Flow

Delivered:

- A customer may request a refund.
- Admin reviews the request.

RefundPending:

- The refund request is open and waiting for admin handling.
- Admin can mark the refund as processing when finance or operations starts working on it.
- Admin can complete the refund when the refund is approved and finished.
- Completing the refund requires a stock result:
    - Good Stock: eligible refunded quantities are restored to inventory.
    - Bad Stock: refunded quantities are tracked but not returned to inventory.

Refunded:

- The refund record is completed.
- The order moves to Refunded.
- Refunded is terminal for the current refund path.

Declined or failed refunds are not currently implemented as a UI action. If this is added later, it should use a separate failed or declined refund result and must not move the order to Refunded.

## Inventory Rules

Inventory is deducted during confirmation or fulfillment. Every mutation writes an inventory log with stock before, stock after, actor, reason, and order context when available.

Inventory restoration is guarded by cancelled and refunded quantities, so the same quantity is not restored twice.

## Stock Movement

Stock movement is handled by `app/Services/InventoryService.php`. Stock changes are transactional and lock the product row before changing `stock_quantity`, so one failed item does not leave the order or inventory in a partial state.

| Movement      | When It Happens                                                | Quantity Change      | Log Type     | Notes                                                                                                                 |
| ------------- | -------------------------------------------------------------- | -------------------- | ------------ | --------------------------------------------------------------------------------------------------------------------- |
| Deduct stock  | Order confirmation or fulfillment                              | Negative             | `deduction`  | Stock cannot go below zero. If any item has insufficient stock, the order action fails and no item stock is deducted. |
| Restore stock | Admin cancellation, partial cancellation, or good-stock refund | Positive             | `restore`    | Restore only the remaining eligible quantity: ordered quantity minus already cancelled and already refunded quantity. |
| Adjust stock  | Admin/manual inventory correction                              | Positive or negative | `adjustment` | Requires a reason and cannot reduce stock below zero.                                                                 |

Stock is not deducted when an order is first created. The order starts as Pending and inventory is deducted only when the order is confirmed or fulfilled.

### Inventory Log Fields

Every stock movement must write an `inventory_logs` row with these values:

| Field                | Purpose                                                                            |
| -------------------- | ---------------------------------------------------------------------------------- |
| `product_id`         | Product whose stock changed.                                                       |
| `order_id`           | Order context when the movement is order-related.                                  |
| `order_item_id`      | Item context when the movement is tied to a specific order item.                   |
| `changed_by_user_id` | User/admin who caused the movement, when available.                                |
| `change_type`        | `deduction`, `restore`, or `adjustment`.                                           |
| `quantity_change`    | Negative for deduction, positive for restore, positive or negative for adjustment. |
| `stock_before`       | Stock quantity before the movement.                                                |
| `stock_after`        | Stock quantity after the movement.                                                 |
| `reason`             | Human-readable reason for the stock change.                                        |
| `metadata`           | Extra context, such as refund id or stock disposition.                             |

### Stock Status

Product stock display uses these states:

| State     | Rule                                                             |
| --------- | ---------------------------------------------------------------- |
| In Stock  | `stock_quantity > low_stock_threshold`                           |
| Low Stock | `stock_quantity > 0` and `stock_quantity <= low_stock_threshold` |
| No Stock  | `stock_quantity = 0`                                             |

Low-stock alerts are queued after stock deduction or stock adjustment when the product is at or below its low-stock threshold. Report and product caches are invalidated after stock changes so dashboards, reports, and inventory tables can refresh with the latest quantities.

## Cleanup

The `orders:cleanup-statuses` command runs hourly. Delivered orders are auto-completed after the configured waiting period, defaulting to 7 days. Fully cancelled partial orders can move to Cancelled when every item quantity is cancelled.

System cleanup writes order activities with a system reason.

## Queues And Cache

Order emails, status side effects, and low-stock alerts are queued after database transactions commit. Report caches are invalidated after product stock changes, order confirmation, cancellation, and refund completion.

## Test Commands

Run backend tests with:

```bash
php artisan test --compact
```

Run frontend checks with:

```bash
npm run build
npm run lint
```
