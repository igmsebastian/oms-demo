import { router } from '@inertiajs/react';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import { RotateCcw, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Order, OrderStatusPayload } from '@/entities/order/model/types';
import { index as ordersIndex, show as orderShow } from '@/routes/orders';
import { DataTable } from '@/shared/components/DataTable';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { StatusMultiSelectFilter } from '@/shared/components/StatusMultiSelectFilter';
import type { PaginatedResource } from '@/shared/types/pagination';

type OrderFilters = Record<string, string | string[]>;

type OrdersTableProps = {
    orders: PaginatedResource<Order>;
    filters: OrderFilters;
    sorts: Record<string, string>;
    statusCounts: Record<string, number>;
    statusOptions: OrderStatusPayload[];
    isAdmin: boolean;
};

export function OrdersTable({
    orders,
    filters,
    sorts,
    statusCounts,
    statusOptions,
    isAdmin,
}: OrdersTableProps) {
    const [keyword, setKeyword] = useState(stringFilter(filters.keyword) ?? '');
    const [loading, setLoading] = useState(false);
    const selectedStatuses = useMemo(
        () => selectedStatusFilters(filters),
        [filters],
    );
    const sorting = useMemo<SortingState>(
        () =>
            Object.entries(sorts ?? {}).map(([id, desc]) => ({
                id,
                desc: desc === 'desc',
            })),
        [sorts],
    );

    const visit = (next: {
        filters?: OrderFilters;
        sorts?: Record<string, string>;
        page?: number;
        perPage?: number;
    }) => {
        setLoading(true);
        router.get(
            ordersIndex.url({
                query: {
                    filters: next.filters ?? filters,
                    sorts: next.sorts ?? sorts,
                    page: next.page ?? 1,
                    per_page: next.perPage ?? orders.meta.per_page,
                },
            }),
            {},
            {
                only: ['orders', 'filters', 'sorts'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setLoading(false),
            },
        );
    };

    const columns = useMemo<ColumnDef<Order>[]>(
        () => [
            {
                accessorKey: 'order_number',
                header: 'Order',
                cell: ({ row }) => (
                    <span className="font-medium">
                        {row.original.order_number}
                    </span>
                ),
            },
            ...(isAdmin
                ? [
                      {
                          id: 'customer',
                          header: 'Customer',
                          cell: ({ row }) => (
                              <div>
                                  <p className="font-medium">
                                      {row.original.customer?.name ??
                                          row.original.user?.name ??
                                          'Customer'}
                                  </p>
                                  <p className="text-xs text-muted-foreground">
                                      {row.original.customer?.email ??
                                          row.original.user?.email}
                                  </p>
                              </div>
                          ),
                      } satisfies ColumnDef<Order>,
                  ]
                : []),
            {
                accessorKey: 'status',
                header: 'Status',
                enableSorting: false,
                cell: ({ row }) => (
                    <StatusBadge
                        status={row.original.status.name}
                        label={row.original.status.label}
                    />
                ),
            },
            {
                accessorKey: 'total_amount',
                header: 'Total',
                cell: ({ row }) => (
                    <MoneyDisplay value={row.original.total_amount} />
                ),
            },
            {
                accessorKey: 'created_at',
                header: 'Created',
                cell: ({ row }) =>
                    new Date(row.original.created_at).toLocaleDateString(),
            },
        ],
        [isAdmin],
    );

    return (
        <DataTable
            data={orders.data}
            columns={columns}
            meta={orders.meta}
            sorting={sorting}
            enableRowSelection
            loading={loading}
            getRowId={(order) => order.id}
            toolbar={
                <form
                    className="grid gap-3 @3xl/main:grid-cols-[minmax(220px,1fr)_220px_auto_auto]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        visit({ filters: { ...filters, keyword } });
                    }}
                >
                    <Input
                        value={keyword}
                        placeholder="Search orders"
                        onChange={(event) => setKeyword(event.target.value)}
                    />
                    <StatusMultiSelectFilter
                        options={statusOptions}
                        selected={selectedStatuses}
                        counts={statusCounts}
                        onChange={(statuses) =>
                            visit({
                                filters: filtersWithStatuses(
                                    filters,
                                    statuses,
                                ),
                            })
                        }
                    />
                    <Button type="submit" variant="outline">
                        <Search className="size-4" />
                        Search
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => {
                            setKeyword('');
                            visit({ filters: {} });
                        }}
                    >
                        <RotateCcw className="size-4" />
                        Reset
                    </Button>
                </form>
            }
            onSortingChange={(next) => {
                const first = next[0];
                visit({
                    sorts: first
                        ? { [first.id]: first.desc ? 'desc' : 'asc' }
                        : {},
                });
            }}
            onPageChange={(page) => visit({ page })}
            onPageSizeChange={(perPage) => visit({ page: 1, perPage })}
            onRowClick={(order) =>
                router.visit(orderShow.url(order.order_number))
            }
            emptyTitle="No orders found"
        />
    );
}

function stringFilter(value: string | string[] | undefined) {
    return Array.isArray(value) ? undefined : value;
}

function selectedStatusFilters(filters: OrderFilters): string[] {
    const value = filters.statuses ?? filters.status;

    if (Array.isArray(value)) {
        return value.filter(Boolean);
    }

    return value ? value.split(',').filter(Boolean) : [];
}

function filtersWithStatuses(
    filters: OrderFilters,
    statuses: string[],
): OrderFilters {
    const nextFilters = { ...filters };

    delete nextFilters.status;
    delete nextFilters.statuses;

    if (statuses.length === 0) {
        return nextFilters;
    }

    return {
        ...nextFilters,
        statuses,
    };
}
