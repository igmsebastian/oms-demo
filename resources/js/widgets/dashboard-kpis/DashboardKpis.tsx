import { AlertTriangle, Boxes, DollarSign, ShoppingCart } from 'lucide-react';
import type { DashboardData } from '@/entities/report/model/types';
import { KpiCard } from '@/shared/components/KpiCard';
import { KpiGrid } from '@/shared/components/KpiGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';

type DashboardKpisProps = {
    data: DashboardData;
};

export function DashboardKpis({ data }: DashboardKpisProps) {
    return (
        <KpiGrid>
            <KpiCard
                title="Total Orders"
                value={data.kpis.total_orders}
                icon={ShoppingCart}
                tone="info"
                footerTitle="Order activity"
                footerDescription="All orders in the current dashboard scope"
            />
            <KpiCard
                title="Pending Orders"
                value={data.kpis.pending_orders}
                icon={Boxes}
                tone="warning"
                footerTitle="Awaiting fulfillment"
                footerDescription="Orders that still need attention"
            />
            <KpiCard
                title="Revenue"
                value={<MoneyDisplay value={data.kpis.revenue} />}
                icon={DollarSign}
                tone="success"
                footerTitle="Gross order value"
                footerDescription="Revenue from the orders shown here"
            />
            <KpiCard
                title="Low Stock Products"
                value={data.kpis.low_stock_products}
                icon={AlertTriangle}
                tone="warning"
                footerTitle="Inventory watchlist"
                footerDescription="Products at or below threshold"
            />
        </KpiGrid>
    );
}
