import { AlertTriangle, Boxes, CircleSlash, Package } from 'lucide-react';
import type { ProductMetrics } from '@/entities/product/model/types';
import { KpiCard } from '@/shared/components/KpiCard';
import { KpiGrid } from '@/shared/components/KpiGrid';

type ProductKpisProps = {
    metrics: ProductMetrics;
    onFilter: (stockStatus: string) => void;
};

export function ProductKpis({ metrics, onFilter }: ProductKpisProps) {
    return (
        <KpiGrid>
            <KpiCard
                title="Total Products"
                value={metrics.total_products}
                icon={Package}
                tone="info"
                onClick={() => onFilter('')}
            />
            <KpiCard
                title="In Stock"
                value={metrics.in_stock_products}
                icon={Boxes}
                tone="success"
                onClick={() => onFilter('in_stock')}
            />
            <KpiCard
                title="Low Stock"
                value={metrics.low_stock_products}
                icon={AlertTriangle}
                tone="warning"
                onClick={() => onFilter('low_stock')}
            />
            <KpiCard
                title="No Stock"
                value={metrics.no_stock_products}
                icon={CircleSlash}
                tone="destructive"
                onClick={() => onFilter('no_stock')}
            />
        </KpiGrid>
    );
}
