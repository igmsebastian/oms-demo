export type DashboardData = {
    kpis: {
        total_orders: number;
        pending_orders: number;
        revenue: number;
        low_stock_products: number;
    };
    status_counts: Record<string, number>;
    status_chart: Array<{ name: string; value: number }>;
    revenue_series: Array<{ period: string; revenue: number; orders: number }>;
};

export type ReportsData = {
    orders: Record<string, number>;
    inventory: Record<string, number>;
    low_stock_count?: number;
    revenue: Record<string, number>;
    series: {
        revenue?: Array<{ date: string; revenue: number; orders: number }>;
        statuses?: Array<{ name: string; value: number }>;
    };
    date_from: string;
    date_to: string;
};
