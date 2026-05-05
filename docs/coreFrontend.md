# Frontend Core Documentation

## 🧭 Start Here

When working on frontend features, start at the route-level page and move downward through the Feature-Sliced Design layers.

Recommended flow:

1. Find the page in `resources/js/pages`.
2. Check widgets used by the page.
3. Check feature actions.
4. Check entity types/status helpers.
5. Check shared components.
6. Check backend props/resources.

Useful commands:

```bash
npm run dev
npm run build
npm run lint
```

## 🧱 Architecture Overview

The frontend follows Feature-Sliced Design.

Layer direction:

```text
shared -> entities -> features -> widgets -> pages
```

Higher layers may import lower layers. Lower layers must not import higher layers. For example, a page can import widgets and shared components, but `shared` must not import from `pages`.

| Layer | Purpose | Example |
| --- | --- | --- |
| `shared` | generic reusable UI/lib/types | `DataTable`, `ConfirmationDialog` |
| `entities` | business objects | `order`, `product`, `user` |
| `features` | user actions | cancel order, refund order |
| `widgets` | composed UI blocks | orders table, order timeline |
| `pages` | route-level screens | Dashboard, Orders |

## 🗂️ Important Frontend Paths

| Concern | Path |
| --- | --- |
| Pages | `resources/js/pages` |
| Shared Components | `resources/js/shared/components` |
| Entities | `resources/js/entities` |
| Features | `resources/js/features` |
| Widgets | `resources/js/widgets` |
| Layouts | `resources/js/layouts` |
| Types | `resources/js/shared/types` or entity model folders |
| Routes/Wayfinder | `resources/js/routes`, `resources/js/actions`, `resources/js/wayfinder` |

## 🎨 UI System

The UI uses a practical component stack:

| Tool | Usage |
| --- | --- |
| ShadCN / Radix UI | accessible primitives and app UI components |
| Tailwind CSS | styling and layout |
| Lucide icons | action icons |
| Sonner | toasts |
| Recharts | charts |
| TanStack Table | tables |
| TanStack Form | field state and validation wiring |
| Zod | frontend schemas |
| Zustand | lightweight UI state, such as open sheets |

Semantic colors:

| Meaning | Color |
| --- | --- |
| Primary | Neutral / black |
| Success | Green |
| Warning | Amber / Yellow |
| Error | Red |
| Info / In Progress | Blue |
| Ghost / Secondary | Neutral |

Keep the interface consistent with the dashboard layout and shared KPI/card/table patterns.

## 📊 Tables

All data tables should use TanStack Table. Backend-paginated lists should use server-side/manual pagination, sorting, and filtering.

Do not filter only the current page. Filters should update query params and request fresh backend data.

Required table features:

- keyword filter
- status filter with counts where applicable
- sorting
- pagination
- ellipsis action dropdown with icons

Main table files:

| Table | Path |
| --- | --- |
| Orders | `resources/js/widgets/orders-table/OrdersTable.tsx` |
| Products | `resources/js/widgets/products-table/ProductsTable.tsx` |
| Generic wrapper | `resources/js/shared/components/DataTable.tsx` |

## ✅ Forms and Validation

Use TanStack Form for field state and Zod for frontend validation. Backend Form Requests remain authoritative.

Rules:

- Use `resources/js/components/ui/field.tsx` for consistent field layout.
- Use `resources/js/shared/forms/TanStackField.tsx` for reusable TanStack field wiring.
- Show backend errors next to the matching field.
- Disable submit while processing.
- Create/update forms should usually appear in a sheet, drawer, or dialog.
- Keep confirmation separate from validation: validate first, then confirm the state-changing action.

Backend validation can still reject requests even if Zod passes. Treat backend errors as the final source of truth.

## 🔔 Toasts and Confirmations

Toasts use Sonner and appear in the upper-right.

Guidelines:

- Use one toast per completed action.
- Use success color/icon for successful actions.
- Use error/danger color/icon for failed actions.
- Every create/update/delete/cancel/refund/status action should use a confirmation dialog.
- Status/cancellation/refund dialogs support optional or required note/reason.
- Do not spam toasts during table filtering or typing.

Shared confirmation component:

- `resources/js/shared/components/ConfirmationDialog.tsx`

## 🧩 Shared Components

| Component | Purpose |
| --- | --- |
| `DataTable` | Generic TanStack table wrapper |
| `StatusBadge` | Semantic status display |
| `KpiCard` | Dashboard/report/product KPI cards |
| `ConfirmationDialog` | Confirm dangerous or state-changing actions |
| `ActionDropdown` | Ellipsis dropdown with icons |
| `PageHeader` | Consistent page titles/actions |
| `EmptyState` | Clean empty display |
| `DateRangeFilter` | Date from/to controls |
| `MoneyDisplay` | Currency formatting |

Shared component path:

```text
resources/js/shared/components
```

## 🛒 Orders UI

Order screens support admin orders and customer-facing My Orders behavior.

Important behavior:

- row click opens order detail
- role-aware KPIs
- status filters with counts
- backend-provided `allowed_actions`
- actions hidden when unavailable
- backend policy still enforces permissions

Order detail sections:

- header
- customer details
- items ledger
- progress timeline
- remarks
- activity logs

Key paths:

| Concern | Path |
| --- | --- |
| Orders page | `resources/js/pages/Orders` |
| Order details page | `resources/js/pages/OrderDetails` |
| Orders table | `resources/js/widgets/orders-table` |
| Items ledger | `resources/js/widgets/order-items-ledger` |
| Activity log | `resources/js/widgets/order-activity-log` |

## 📦 Products / Inventory UI

Products/Inventory is admin-only.

Behavior:

- KPI cards can filter the table.
- Create/update product uses a sheet.
- `Create another` keeps the product sheet open and returns focus to SKU.
- Stock badges show in stock, low stock, and no stock states.
- Product taxonomy references are loaded for category, brand, unit, size, color, and tags.
- Optional product cards may be used where they improve scanning.

Key paths:

| Concern | Path |
| --- | --- |
| Products page | `resources/js/pages/Products` |
| Products table | `resources/js/widgets/products-table` |
| Product KPIs | `resources/js/widgets/product-kpis` |
| Product form sheet | `resources/js/features/product-form` |
| Product management | `resources/js/pages/ProductManagement` |

## 📈 Reports UI

Reports are admin-only.

Behavior:

- date filters drive report data
- report type dropdown changes the report context
- default date range:
  - first day of last month
  - last day of current month
- Excel export is handled by a backend endpoint using `maatwebsite/excel`

Key paths:

| Concern | Path |
| --- | --- |
| Reports page | `resources/js/pages/Reports` |
| Report export controller | `app/Http/Controllers/ReportExportController.php` |
| Report service | `app/Services/ReportService.php` |
| Excel exports | `app/Exports` |

## ⚙️ Settings

The existing settings layout is preserved.

Profile uses:

- `first_name`
- `middle_name`
- `last_name`
- `email`

The `name` compatibility accessor may still exist for older frontend/backend expectations.

Key paths:

| Concern | Path |
| --- | --- |
| Profile page | `resources/js/pages/settings/profile.tsx` |
| Security page | `resources/js/pages/settings/security.tsx` |
| Settings routes | `routes/settings.php` |
| Profile controller | `app/Http/Controllers/Settings/ProfileController.php` |
| Security controller | `app/Http/Controllers/Settings/SecurityController.php` |

## 🧯 Troubleshooting Guide

| Problem | Where to Check |
| --- | --- |
| Page data missing | Inertia controller props / resource |
| Button not showing | backend `allowed_actions` / policy / role |
| Table filter not working | query params, filter class, `DataTable` |
| Toast not appearing | Sonner provider/layout |
| Form validation mismatch | Zod schema and Form Request |
| Chart empty | report service data / props |
| Navigation wrong | layout/sidebar config and auth user role |
| Build fails | TypeScript types/import paths |

## 🧪 Frontend Verification

```bash
npm run build
npm run lint
npm run dev
```

What each command catches:

| Command | Purpose |
| --- | --- |
| `npm run build` | production build, TypeScript/import issues, generated assets |
| `npm run lint` | lint and quality issues if configured |
| `npm run dev` | Vite dev server for local UI work |

When a page looks stale, rebuild frontend assets or make sure Vite is running.
# Frontend Core Documentation

## 🧭 Start Here

When working on frontend features, start at the page and walk down through the layers it composes.

Recommended flow:

1. Find the page in `resources/js/pages`.
2. Check widgets used by the page.
3. Check feature actions.
4. Check entity types/status helpers.
5. Check shared components.
6. Check backend props/resources.

If page data looks wrong, check the Inertia controller props or API Resource before changing the component.

## 🧱 Architecture Overview

The frontend follows Feature-Sliced Design.

Layer direction:

```text
shared -> entities -> features -> widgets -> pages
```

Higher layers may import lower layers. Lower layers must not import higher layers.

| Layer | Purpose | Example |
|---|---|---|
| `shared` | generic reusable UI/lib/types | `DataTable`, `ConfirmationDialog` |
| `entities` | business objects | `order`, `product`, `user` |
| `features` | user actions | cancel order, refund order |
| `widgets` | composed UI blocks | orders table, order timeline |
| `pages` | route-level screens | Dashboard, Orders |

Keep business-specific rendering close to its entity or feature. Keep generic UI reusable in `shared`.

## 🗂️ Important Frontend Paths

| Concern | Path |
|---|---|
| Pages | `resources/js/pages` |
| Shared Components | `resources/js/shared/components` |
| Entities | `resources/js/entities` |
| Features | `resources/js/features` |
| Widgets | `resources/js/widgets` |
| Layouts | `resources/js/layouts` |
| Types | `resources/js/shared/types` or entity model folders |
| Routes/Wayfinder | `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder` |

## 🎨 UI System

The UI stack is built around:

- ShadCN / Radix UI primitives
- Tailwind CSS
- Lucide icons
- Sonner toasts
- Recharts
- TanStack Table
- TanStack Form
- Zod
- Zustand only for UI state when a local store is justified

Semantic colors:

| Meaning | Color |
|---|---|
| Primary | Neutral / black |
| Success | Green |
| Warning | Amber / Yellow |
| Error | Red |
| Info / In Progress | Blue |
| Ghost / Secondary | Neutral |

Use the shared status/color helpers for order status badges, filters, timelines, charts, and KPI cards so colors stay consistent.

## 📊 Tables

All tables use TanStack Table.

Backend-paginated lists must use server-side/manual pagination, sorting, and filtering. Do not filter only the current page. Filters should update query params and request fresh backend data from the Inertia controller or API endpoint.

Required table features:

- keyword filter
- status filter with counts where applicable
- sorting
- pagination
- ellipsis action dropdown with icons

When a table changes page, filter, or sort, the backend query should be the source of truth.

## ✅ Forms and Validation

Use TanStack Form with Zod for frontend validation. Backend validation remains authoritative and is implemented through Laravel Form Requests.

Form expectations:

- Use ShadCN form, dialog, sheet, drawer, popover, and field components.
- Create/update forms usually appear in a modal dialog, sheet, or drawer.
- Disable submit while processing.
- Show validation errors clearly near the field.
- Mark required fields with the shared required-label pattern.
- Keep API/server validation messages friendly and direct.

Frontend validation should improve the user experience, not replace backend validation.

## 🔔 Toasts and Confirmations

Sonner toasts appear in the upper-right.

Rules:

- One toast per completed action.
- Success toasts use success styling and matching icon.
- Error toasts use danger/error styling and matching icon.
- Every create/update/delete/cancel/refund/status action requires a confirmation dialog.
- Status, cancellation, and refund dialogs support optional or required note/reason depending on the action.
- Do not spam toasts for the same action.

## 🧩 Shared Components

| Component | Purpose |
|---|---|
| `DataTable` | Generic TanStack table wrapper |
| `StatusBadge` | Semantic status display |
| `KpiCard` | Dashboard/report/product KPI cards |
| `ConfirmationDialog` | Confirm dangerous or state-changing actions |
| `ActionDropdown` | Ellipsis dropdown with icons |
| `PageHeader` | Consistent page titles/actions |
| `EmptyState` | Clean empty display |
| `DateRangeFilter` | Date from/to controls |
| `MoneyDisplay` | Currency formatting |

Before creating a new component, check `resources/js/shared/components` and the relevant entity/widget folder.

## 🛒 Orders UI

Orders UI includes admin Orders and customer My Orders behavior.

Important behavior:

- Row click opens the order detail page.
- KPIs are role-aware.
- Status filters support status colors and counts.
- `allowed_actions` comes from the backend.
- UI hides unavailable actions.
- Backend policy still enforces permissions.

Order detail cards:

- header
- customer details
- items ledger
- progress timeline
- remarks
- activity logs

The progress timeline should render status progression consistently with backend status transitions.

## 📦 Products / Inventory UI

Products and Inventory pages are admin only.

Expected behavior:

- KPI cards can filter the table.
- Create/update product uses a sheet/drawer.
- Create another checkbox stays in the footer for create flow.
- Stock badge states are `In Stock`, `Low Stock`, and `No Stock`.
- Product taxonomy references come from backend props.
- Optional product cards can be used when they help scanning, but table behavior remains backend-driven.

## 📈 Reports UI

Reports are admin only.

Expected behavior:

- Date filters control report data and exports.
- Default date range is first day of last month through last day of current month.
- Report type dropdown changes the displayed summary/chart data.
- Excel export uses the backend `maatwebsite/excel` endpoint.
- Download Report action should use the selected report type and date range.

## ⚙️ Settings

The existing settings layout is preserved.

Profile uses:

- `first_name`
- `middle_name`
- `last_name`
- `email`

`name` compatibility may still exist for old frontend expectations, but new UI should prefer the explicit name fields.

## 🧯 Troubleshooting Guide

| Problem | Where to Check |
|---|---|
| Page data missing | Inertia controller props / resource |
| Button not showing | backend `allowed_actions` / policy / role |
| Table filter not working | query params, filter class, `DataTable` |
| Toast not appearing | Sonner provider/layout |
| Form validation mismatch | Zod schema and Form Request |
| Chart empty | report service data / props |
| Navigation wrong | layout/sidebar config and auth user role |
| Build fails | TypeScript types/import paths |

## 🧪 Frontend Verification

```bash
npm run build
npm run lint
npm run dev
```

`npm run build` catches TypeScript, Vite, and import issues.

`npm run lint` catches quality issues if configured.

`npm run dev` runs Vite for local frontend development.
