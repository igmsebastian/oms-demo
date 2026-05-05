import { Head, usePage } from '@inertiajs/react';
import { BadgeCheck, Boxes, Clock, PackageCheck, Truck } from 'lucide-react';
import type { Order, OrderStatusPayload } from '@/entities/order/model/types';
import { index as ordersIndex } from '@/routes/orders';
import { KpiCard } from '@/shared/components/KpiCard';
import { KpiGrid } from '@/shared/components/KpiGrid';
import { PageHeader } from '@/shared/components/PageHeader';
import type { PaginatedResource } from '@/shared/types/pagination';
import type { Auth } from '@/types';
import { OrdersTable } from '@/widgets/orders-table/OrdersTable';

type OrdersProps = {
    orders: PaginatedResource<Order>;
    filters: Record<string, string | string[]>;
    sorts: Record<string, string>;
    status_counts: Record<string, number>;
    status_options: OrderStatusPayload[];
    is_admin: boolean;
};

export default function Orders({
    orders,
    filters,
    sorts,
    status_counts,
    status_options,
    is_admin,
}: OrdersProps) {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const title = auth.user?.is_admin ? 'Orders' : 'My Orders';

    return (
        <>
            <Head title={title} />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="px-4 lg:px-6">
                            <PageHeader
                                title={title}
                                description="Latest orders are shown first."
                            />
                        </div>

                        <KpiGrid>
                            <KpiCard
                                title="Pending"
                                value={status_counts.pending ?? 0}
                                icon={Clock}
                                tone="warning"
                                footerTitle="Needs review"
                                footerDescription="Orders waiting for confirmation"
                            />
                            <KpiCard
                                title="Processing"
                                value={status_counts.processing ?? 0}
                                icon={Boxes}
                                tone="info"
                                footerTitle="Warehouse queue"
                                footerDescription="Orders being picked or prepared"
                            />
                            <KpiCard
                                title="Shipped"
                                value={status_counts.shipped ?? 0}
                                icon={Truck}
                                tone="info"
                                footerTitle="Carrier handoff"
                                footerDescription="Orders already moving to customers"
                            />
                            <KpiCard
                                title={is_admin ? 'Delivered' : 'Completed'}
                                value={
                                    is_admin
                                        ? (status_counts.delivered ?? 0)
                                        : (status_counts.completed ?? 0)
                                }
                                icon={is_admin ? PackageCheck : BadgeCheck}
                                tone="success"
                                footerTitle={
                                    is_admin
                                        ? 'Arrived orders'
                                        : 'Closed orders'
                                }
                                footerDescription={
                                    is_admin
                                        ? 'Carrier marked delivered'
                                        : 'Fulfillment lifecycle finished'
                                }
                            />
                        </KpiGrid>

                        <div className="px-4 lg:px-6">
                            <OrdersTable
                                orders={orders}
                                filters={filters}
                                sorts={sorts}
                                statusCounts={status_counts}
                                statusOptions={status_options}
                                isAdmin={is_admin}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Orders.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex(),
        },
    ],
};
