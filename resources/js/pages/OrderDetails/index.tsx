import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import {
    BadgeCheck,
    Banknote,
    Ban,
    CircleCheck,
    History,
    Mail,
    MapPin,
    PackageCheck,
    RefreshCcw,
    ShoppingBag,
    Truck,
    User,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import type {
    Order,
    OrderActivity,
    OrderStatusPayload,
} from '@/entities/order/model/types';
import { OrderCancellationAction } from '@/features/order-cancellation/OrderCancellationAction';
import { OrderRefundAction } from '@/features/order-refund/OrderRefundAction';
import { OrderStatusAction } from '@/features/order-status-change/OrderStatusAction';
import { cn } from '@/lib/utils';
import { index as ordersIndex, show as orderShow } from '@/routes/orders';
import { store as storeRemark } from '@/routes/orders/remarks';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { PageHeader } from '@/shared/components/PageHeader';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { fieldErrors } from '@/shared/forms/errors';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { statusTone } from '@/shared/status/statusTheme';
import type { StatusTone } from '@/shared/status/statusTheme';
import { OrderActivityTable } from '@/widgets/order-activity-log/OrderActivityTable';
import { OrderItemsLedger } from '@/widgets/order-items-ledger/OrderItemsLedger';
import { OrderTimeline } from '@/widgets/order-timeline/OrderTimeline';

type OrderDetailsProps = {
    order: Order;
    status_options: OrderStatusPayload[];
};

type MaybeResourceCollection<T> =
    | T[]
    | {
          data?: T[];
      }
    | null
    | undefined;

const remarkSchema = z.object({
    note: z
        .string()
        .trim()
        .min(1, 'Enter a remark.')
        .max(300, 'Remark may not be longer than 300 characters.'),
});

const statusActions = {
    processing: {
        label: 'Start Processing',
        icon: PackageCheck,
        tone: 'info' as const,
    },
    packed: { label: 'Mark Packed', icon: PackageCheck, tone: 'info' as const },
    shipped: { label: 'Mark Shipped', icon: Truck, tone: 'info' as const },
    delivered: {
        label: 'Mark Delivered',
        icon: CircleCheck,
        tone: 'success' as const,
    },
    completed: {
        label: 'Mark Completed',
        icon: BadgeCheck,
        tone: 'success' as const,
    },
};

const overviewToneClasses: Record<
    StatusTone,
    {
        tile: string;
        icon: string;
        label: string;
        value: string;
        description: string;
    }
> = {
    neutral: {
        tile: 'border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/30',
        icon: 'bg-slate-200/80 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        label: 'text-slate-600 dark:text-slate-300',
        value: 'text-slate-950 dark:text-slate-50',
        description: 'text-slate-500 dark:text-slate-400',
    },
    success: {
        tile: 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900 dark:bg-emerald-950/25',
        icon: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/70 dark:text-emerald-200',
        label: 'text-emerald-700 dark:text-emerald-300',
        value: 'text-emerald-950 dark:text-emerald-50',
        description: 'text-emerald-700/75 dark:text-emerald-300/75',
    },
    warning: {
        tile: 'border-amber-200 bg-amber-50/80 dark:border-amber-900 dark:bg-amber-950/25',
        icon: 'bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-200',
        label: 'text-amber-700 dark:text-amber-300',
        value: 'text-amber-950 dark:text-amber-50',
        description: 'text-amber-700/75 dark:text-amber-300/75',
    },
    destructive: {
        tile: 'border-red-200 bg-red-50/80 dark:border-red-900 dark:bg-red-950/25',
        icon: 'bg-red-100 text-red-700 dark:bg-red-900/70 dark:text-red-200',
        label: 'text-red-700 dark:text-red-300',
        value: 'text-red-950 dark:text-red-50',
        description: 'text-red-700/75 dark:text-red-300/75',
    },
    info: {
        tile: 'border-blue-200 bg-blue-50/80 dark:border-blue-900 dark:bg-blue-950/25',
        icon: 'bg-blue-100 text-blue-700 dark:bg-blue-900/70 dark:text-blue-200',
        label: 'text-blue-700 dark:text-blue-300',
        value: 'text-blue-950 dark:text-blue-50',
        description: 'text-blue-700/75 dark:text-blue-300/75',
    },
};

export default function OrderDetails({ order: rawOrder }: OrderDetailsProps) {
    const order = useMemo(
        () => normalizeOrderCollections(rawOrder),
        [rawOrder],
    );
    const activities = resourceArray(order.activities);
    const refunds = resourceArray(order.refunds);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Orders',
                href: ordersIndex(),
            },
            {
                title: order.order_number,
                href: orderShow(order.order_number),
            },
        ],
    });

    return (
        <>
            <Head title={order.order_number} />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="px-4 lg:px-6">
                            <PageHeader
                                title={order.order_number}
                                description={`Created ${new Date(order.created_at).toLocaleString()}`}
                                actions={<OrderCtas order={order} />}
                            />
                        </div>

                        <div className="px-4 lg:px-6">
                            <OrderOverview
                                order={order}
                                activities={activities}
                            />
                        </div>

                        <div className="grid gap-4 px-4 lg:px-6 xl:grid-cols-[minmax(280px,360px)_minmax(0,1fr)] xl:items-start">
                            <div className="space-y-4 xl:sticky xl:top-20 xl:self-start">
                                <CustomerCard order={order} />
                            </div>
                            <div className="min-w-0 space-y-4">
                                <OrderItemsLedger
                                    items={order.items ?? []}
                                    latestRefund={refunds.at(-1) ?? null}
                                />
                                <OrderTimeline
                                    activities={activities}
                                    currentStatus={order.status.name}
                                    orderNumber={order.order_number}
                                />
                                <OrderActivityTable
                                    activities={activities}
                                    orderNumber={order.order_number}
                                    composer={
                                        <RemarksForm
                                            orderNumber={order.order_number}
                                        />
                                    }
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function OrderCtas({ order }: { order: Order }) {
    const allowedActions = resourceArray(order.allowed_actions);
    const availableByName = Object.fromEntries(
        resourceArray(order.available_statuses).map((status) => [
            status.name,
            status,
        ]),
    );

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {allowedActions.includes('fulfill') && (
                <OrderStatusAction
                    order={order}
                    action="fulfill"
                    label="Fulfill"
                    icon={PackageCheck}
                    tone="info"
                />
            )}
            {Object.entries(statusActions).map(([statusName, config]) =>
                allowedActions.includes(actionName(statusName)) &&
                availableByName[statusName] ? (
                    <OrderStatusAction
                        key={statusName}
                        order={order}
                        action="status"
                        status={availableByName[statusName]}
                        label={config.label}
                        icon={config.icon}
                        tone={config.tone}
                    />
                ) : null,
            )}
            {allowedActions.includes('cancel') && (
                <OrderCancellationAction order={order} mode="cancel" />
            )}
            {allowedActions.includes('request_cancellation') && (
                <OrderCancellationAction order={order} mode="request" />
            )}
            {allowedActions.includes('request_refund') && (
                <OrderRefundAction order={order} mode="request" />
            )}
            {allowedActions.includes('mark_refund_processing') && (
                <OrderRefundAction order={order} mode="processing" />
            )}
            {allowedActions.includes('mark_refunded') && (
                <OrderRefundAction order={order} mode="complete" />
            )}
        </div>
    );
}

function actionName(statusName: string): string {
    return (
        {
            processing: 'start_processing',
            packed: 'mark_packed',
            shipped: 'mark_shipped',
            delivered: 'mark_delivered',
            completed: 'mark_completed',
        }[statusName] ?? 'update_status'
    );
}

function OrderOverview({
    order,
    activities,
}: {
    order: Order;
    activities: OrderActivity[];
}) {
    const items = resourceArray(order.items);
    const refunds = resourceArray(order.refunds);
    const latestRefund = refunds.at(-1);
    const cancellationTone: StatusTone = order.cancellation_reason
        ? 'destructive'
        : 'neutral';
    const refundTone = latestRefund
        ? statusTone(latestRefund.status.name)
        : 'neutral';

    return (
        <Card className="rounded-lg shadow-none">
            <CardContent className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
                <OverviewMetric
                    icon={PackageCheck}
                    label="Status"
                    tone={statusTone(order.status.name)}
                    value={
                        <StatusBadge
                            status={order.status.name}
                            label={order.status.label}
                        />
                    }
                    description={`${activities.length} activity ${activities.length === 1 ? 'entry' : 'entries'}`}
                />
                <OverviewMetric
                    icon={Banknote}
                    label="Total"
                    tone="success"
                    value={<MoneyDisplay value={order.total_amount} />}
                    description={`${items.length} item ${items.length === 1 ? 'line' : 'lines'}`}
                />
                <OverviewMetric
                    icon={order.cancellation_reason ? Ban : History}
                    label="Cancellation"
                    tone={cancellationTone}
                    value={order.cancellation_reason ?? 'None'}
                    description={
                        order.cancellation_reason
                            ? 'Reason captured'
                            : 'No cancellation reason'
                    }
                />
                <OverviewMetric
                    icon={latestRefund ? RefreshCcw : ShoppingBag}
                    label="Refund"
                    tone={refundTone}
                    value={latestRefund?.status.label ?? 'None'}
                    description={
                        latestRefund
                            ? 'Latest refund state'
                            : 'No refund activity'
                    }
                />
            </CardContent>
        </Card>
    );
}

function OverviewMetric({
    icon: Icon,
    label,
    value,
    description,
    tone,
}: {
    icon: LucideIcon;
    label: string;
    value: ReactNode;
    description: string;
    tone: StatusTone;
}) {
    const classes = overviewToneClasses[tone];

    return (
        <div
            className={cn(
                'flex min-w-0 gap-3 rounded-lg border p-4 transition-colors',
                classes.tile,
            )}
        >
            <div
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-lg',
                    classes.icon,
                )}
            >
                <Icon className="size-5" />
            </div>
            <div className="min-w-0">
                <p className={cn('text-sm', classes.label)}>{label}</p>
                <div
                    className={cn(
                        'mt-1 truncate text-lg font-semibold',
                        classes.value,
                    )}
                >
                    {value}
                </div>
                <p className={cn('mt-1 truncate text-xs', classes.description)}>
                    {description}
                </p>
            </div>
        </div>
    );
}

function CustomerCard({ order }: { order: Order }) {
    return (
        <Card className="rounded-lg shadow-none">
            <CardHeader className="pb-3">
                <CardTitle>Customer Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <CustomerDetail
                    icon={User}
                    value={order.customer?.name ?? order.user?.name}
                    label="Customer"
                />
                <CustomerDetail
                    icon={Mail}
                    value={order.customer?.email ?? order.user?.email}
                    label="Email"
                />
                <CustomerDetail
                    icon={MapPin}
                    value={order.shipping_full_address}
                    label="Shipping address"
                />
            </CardContent>
        </Card>
    );
}

function CustomerDetail({
    icon: Icon,
    value,
    label,
}: {
    icon: LucideIcon;
    value?: string | null;
    label: string;
}) {
    return (
        <div className="flex gap-3">
            <Icon className="mt-0.5 size-4 text-muted-foreground" />
            <div className="min-w-0">
                <p className="font-medium break-words">{value ?? '-'}</p>
                <p className="text-sm text-muted-foreground">{label}</p>
            </div>
        </div>
    );
}

function RemarksForm({ orderNumber }: { orderNumber: string }) {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            note: '',
        },
        validators: {
            onSubmit: remarkSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = storeRemark.post(orderNumber);

            router.visit(request.url, {
                method: request.method,
                data: value,
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => form.reset(),
                onError: (backendErrors) => {
                    setServerErrors(backendErrors);
                    toast.error(
                        'We could not add the remark. Please try again.',
                    );
                },
                onFinish: () => setProcessing(false),
            });
        },
    });

    return (
        <div className="rounded-lg border bg-muted/20 p-4">
            <form
                id="order-remark-form"
                className="space-y-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    void form.handleSubmit();
                }}
            >
                <form.Field
                    name="note"
                    children={(field) => {
                        const errors = fieldErrors(
                            field.state.meta.errors,
                            serverErrors.note,
                        );
                        const isInvalid = errors.length > 0;

                        return (
                            <Field data-invalid={isInvalid}>
                                <FieldLabel htmlFor={field.name} required>
                                    Add Remark
                                </FieldLabel>
                                <textarea
                                    id={field.name}
                                    name={field.name}
                                    value={field.state.value}
                                    maxLength={300}
                                    placeholder="Add an internal order remark"
                                    onBlur={field.handleBlur}
                                    onChange={(event) =>
                                        field.handleChange(event.target.value)
                                    }
                                    aria-invalid={isInvalid}
                                    className="min-h-24 w-full resize-none rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                />
                                <div className="flex items-start justify-between gap-3">
                                    <FieldError errors={errors} />
                                    <p className="ml-auto shrink-0 text-xs text-muted-foreground">
                                        {field.state.value.length}/300
                                    </p>
                                </div>
                            </Field>
                        );
                    }}
                />
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        form="order-remark-form"
                        disabled={processing}
                    >
                        {processing ? 'Saving...' : 'Submit'}
                    </Button>
                </div>
            </form>
        </div>
    );
}

function normalizeOrderCollections(order: Order): Order {
    return {
        ...order,
        activities: resourceArray(order.activities),
        allowed_actions: resourceArray(order.allowed_actions),
        available_statuses: resourceArray(order.available_statuses),
        items: resourceArray(order.items),
        refunds: resourceArray(order.refunds),
    };
}

function resourceArray<T>(value: MaybeResourceCollection<T>): T[] {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && Array.isArray(value.data)) {
        return value.data;
    }

    return [];
}
