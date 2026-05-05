import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OrderItem, OrderRefund } from '@/entities/order/model/types';
import { DataTable } from '@/shared/components/DataTable';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';

type OrderItemsLedgerProps = {
    items: OrderItem[];
    latestRefund?: OrderRefund | null;
};

export function OrderItemsLedger({
    items,
    latestRefund = null,
}: OrderItemsLedgerProps) {
    const columns = useMemo<ColumnDef<OrderItem>[]>(
        () => [
            {
                accessorKey: 'id',
                header: 'Item',
                cell: ({ row }) => row.original.id.slice(-8).toUpperCase(),
            },
            {
                accessorKey: 'product_sku',
                header: 'SKU',
            },
            {
                accessorKey: 'product_name',
                header: 'Product',
                cell: ({ row }) => (
                    <div>
                        <p className="font-medium">
                            {row.original.product_name}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {row.original.product?.category?.name ??
                                'Uncategorized'}
                        </p>
                    </div>
                ),
            },
            {
                id: 'tags',
                header: 'Tags',
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-1">
                        {row.original.product?.tags?.slice(0, 3).map((tag) => (
                            <StatusBadge
                                key={tag.id}
                                status="active"
                                label={tag.name}
                            />
                        ))}
                    </div>
                ),
            },
            {
                accessorKey: 'quantity',
                header: 'Qty',
            },
            {
                accessorKey: 'unit_price',
                header: 'Unit Price',
                cell: ({ row }) => (
                    <MoneyDisplay value={row.original.unit_price} />
                ),
            },
            {
                accessorKey: 'line_total',
                header: 'Total',
                cell: ({ row }) => (
                    <MoneyDisplay value={row.original.line_total} />
                ),
            },
            {
                id: 'adjustments',
                header: 'Adjustments',
                enableSorting: false,
                cell: ({ row }) => (
                    <AdjustmentCell
                        item={row.original}
                        latestRefund={latestRefund}
                    />
                ),
            },
        ],
        [latestRefund],
    );

    return (
        <Card className="rounded-lg shadow-none">
            <CardHeader className="pb-3">
                <CardTitle>Items Ledger</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable
                    data={items}
                    columns={columns}
                    enableColumnVisibility={false}
                    emptyTitle="No items found"
                />
            </CardContent>
        </Card>
    );
}

function AdjustmentCell({
    item,
    latestRefund,
}: {
    item: OrderItem;
    latestRefund?: OrderRefund | null;
}) {
    const hasCancellation = item.cancelled_quantity > 0;
    const hasRefund = item.refunded_quantity > 0;

    if (!hasCancellation && !hasRefund) {
        return <span className="text-muted-foreground">None</span>;
    }

    return (
        <div className="flex min-w-40 flex-col gap-1.5 whitespace-normal">
            {hasCancellation && (
                <div className="flex items-center justify-between gap-3">
                    <span className="text-xs text-muted-foreground">
                        Cancelled
                    </span>
                    <StatusBadge
                        status="cancelled"
                        label={`${item.cancelled_quantity} of ${item.quantity}`}
                    />
                </div>
            )}
            {hasRefund && (
                <div className="flex items-center justify-between gap-3">
                    <span className="text-xs text-muted-foreground">
                        Refunded
                    </span>
                    <StatusBadge
                        status="refunded"
                        label={`${item.refunded_quantity} of ${item.quantity}`}
                    />
                </div>
            )}
            {hasRefund && (
                <div className="flex items-center justify-between gap-3">
                    <span className="text-xs text-muted-foreground">
                        Stock result
                    </span>
                    <StatusBadge
                        status={stockDispositionStatus(latestRefund)}
                        label={formatStockDisposition(latestRefund)}
                    />
                </div>
            )}
        </div>
    );
}

function stockDispositionStatus(refund?: OrderRefund | null): string {
    if (refund?.stock_disposition === 'bad_stock') {
        return 'no_stock';
    }

    if (refund?.stock_disposition === 'good_stock') {
        return 'in_stock';
    }

    return 'refund_pending';
}

function formatStockDisposition(refund?: OrderRefund | null): string {
    if (refund?.stock_disposition === 'good_stock') {
        return 'Good Stock';
    }

    if (refund?.stock_disposition === 'bad_stock') {
        return 'Bad Stock';
    }

    return 'Pending Review';
}
