import { Head } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { BarChart3, Boxes, CircleDollarSign, ShoppingCart } from 'lucide-react';
import { useMemo } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    Cell,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ReportsData } from '@/entities/report/model/types';
import { index as reportsIndex } from '@/routes/reports';
import {
    chartBlue,
    chartTooltipProps,
    inventoryChartColor,
    statusChartColor,
} from '@/shared/charts/chartTheme';
import { DataTable } from '@/shared/components/DataTable';
import { KpiCard } from '@/shared/components/KpiCard';
import { KpiGrid } from '@/shared/components/KpiGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { PageHeader } from '@/shared/components/PageHeader';
import { ReportsFilterBar } from '@/widgets/reports-filter-bar/ReportsFilterBar';

type ReportsProps = {
    reports: ReportsData;
    filters: {
        date_from?: string | null;
        date_to?: string | null;
        type?: string | null;
    };
};

type SummaryRow = {
    id: string;
    section: string;
    metric: string;
    value: number;
    money: boolean;
};

type ReportType = 'orders' | 'inventory' | 'revenue';

const reportLabels: Record<ReportType, string> = {
    inventory: 'Inventory',
    orders: 'Orders',
    revenue: 'Revenue',
};
const numberFormatter = new Intl.NumberFormat('en-US');
const compactMoneyFormatter = new Intl.NumberFormat('en-US', {
    currency: 'USD',
    maximumFractionDigits: 0,
    notation: 'compact',
    style: 'currency',
});
const moneyFormatter = new Intl.NumberFormat('en-US', {
    currency: 'USD',
    style: 'currency',
});

export default function Reports({ reports, filters }: ReportsProps) {
    const dateFrom = reports.date_from ?? filters.date_from ?? '';
    const dateTo = reports.date_to ?? filters.date_to ?? '';
    const selectedType = isReportType(filters.type) ? filters.type : 'orders';
    const lowStockProducts =
        reports.low_stock_count ?? reports.inventory.low_stock_products ?? 0;

    return (
        <>
            <Head title="Reports" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="px-4 lg:px-6">
                            <PageHeader
                                title="Reports"
                                description={`${reportLabels[selectedType]} report for ${formatDateRange(dateFrom, dateTo)}.`}
                            />
                        </div>

                        <KpiGrid>
                            <KpiCard
                                title="Total Orders"
                                value={reports.orders.total ?? 0}
                                icon={ShoppingCart}
                                tone="info"
                                footerTitle="Filtered range"
                                footerDescription={formatDateRange(
                                    dateFrom,
                                    dateTo,
                                )}
                            />
                            <KpiCard
                                title="Gross Revenue"
                                value={
                                    <MoneyDisplay
                                        value={
                                            reports.revenue.gross_revenue ?? 0
                                        }
                                    />
                                }
                                icon={CircleDollarSign}
                                tone="success"
                                footerTitle="Order value"
                                footerDescription="Gross revenue from filtered orders"
                            />
                            <KpiCard
                                title="Completed Revenue"
                                value={
                                    <MoneyDisplay
                                        value={
                                            reports.revenue.completed_revenue ??
                                            0
                                        }
                                    />
                                }
                                icon={BarChart3}
                                tone="success"
                                footerTitle="Closed sales"
                                footerDescription="Revenue from completed orders"
                            />
                            <KpiCard
                                title="Low Stock Products"
                                value={lowStockProducts}
                                icon={Boxes}
                                tone="warning"
                                footerTitle="Inventory watchlist"
                                footerDescription="Products at or below threshold"
                            />
                        </KpiGrid>

                        <div className="px-4 lg:px-6">
                            <ReportsFilterBar
                                filters={{
                                    ...filters,
                                    date_from: dateFrom,
                                    date_to: dateTo,
                                    type: selectedType,
                                }}
                            />
                        </div>

                        <ReportCharts reports={reports} type={selectedType} />

                        <div className="px-4 lg:px-6">
                            <ReportsSummaryTable
                                reports={reports}
                                type={selectedType}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Reports.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: reportsIndex(),
        },
    ],
};

function ReportCharts({
    reports,
    type,
}: {
    reports: ReportsData;
    type: ReportType;
}) {
    if (type === 'inventory') {
        return (
            <div className="px-4 lg:px-6">
                <InventoryStatusCard data={reports.inventory} />
            </div>
        );
    }

    if (type === 'revenue') {
        return (
            <div className="px-4 lg:px-6">
                <RevenueTrendCard data={reports.series.revenue ?? []} />
            </div>
        );
    }

    return (
        <div className="grid gap-4 px-4 lg:px-6 xl:grid-cols-2">
            <OrdersTrendCard data={reports.series.revenue ?? []} />
            <StatusDistributionCard data={reports.series.statuses ?? []} />
        </div>
    );
}

function OrdersTrendCard({ data }: { data: ReportsData['series']['revenue'] }) {
    return (
        <Card className="@container/card rounded-xl shadow-xs">
            <CardHeader>
                <CardTitle>Orders over time</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Daily order volume in the filtered range
                    </span>
                    <span className="@[540px]/card:hidden">Daily orders</span>
                </CardDescription>
                <CardAction>
                    <Badge variant="outline">
                        {data?.reduce(
                            (total, item) => total + item.orders,
                            0,
                        ) ?? 0}{' '}
                        orders
                    </Badge>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {data && data.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <AreaChart data={data}>
                            <defs>
                                <linearGradient
                                    id="fillReportOrders"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor={chartBlue}
                                        stopOpacity={0.45}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor={chartBlue}
                                        stopOpacity={0.08}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="date"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={32}
                                tickFormatter={formatDateTick}
                            />
                            <YAxis
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                                width={36}
                            />
                            <Tooltip
                                {...chartTooltipProps}
                                cursor={false}
                                labelFormatter={(value) =>
                                    formatDateLabel(String(value))
                                }
                                formatter={(value) => [
                                    numberFormatter.format(Number(value)),
                                    'Orders',
                                ]}
                            />
                            <Area
                                dataKey="orders"
                                type="natural"
                                fill="url(#fillReportOrders)"
                                stroke={chartBlue}
                                strokeWidth={2}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="flex h-[250px] items-center justify-center text-sm text-muted-foreground">
                        No order data available
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function RevenueTrendCard({
    data,
}: {
    data: ReportsData['series']['revenue'];
}) {
    return (
        <Card className="@container/card rounded-xl shadow-xs">
            <CardHeader>
                <CardTitle>Revenue over time</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Revenue and order volume by day
                    </span>
                    <span className="@[540px]/card:hidden">Daily revenue</span>
                </CardDescription>
                <CardAction>
                    <Badge variant="outline">{data?.length ?? 0} days</Badge>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {data && data.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <AreaChart data={data}>
                            <defs>
                                <linearGradient
                                    id="fillReportRevenue"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor={chartBlue}
                                        stopOpacity={0.45}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor={chartBlue}
                                        stopOpacity={0.08}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="date"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={32}
                                tickFormatter={formatDateTick}
                            />
                            <YAxis
                                tickLine={false}
                                axisLine={false}
                                width={52}
                                tickFormatter={(value) =>
                                    compactMoneyFormatter.format(Number(value))
                                }
                            />
                            <Tooltip
                                {...chartTooltipProps}
                                cursor={false}
                                labelFormatter={(value) =>
                                    formatDateLabel(String(value))
                                }
                                formatter={(value, name) => [
                                    name === 'revenue'
                                        ? moneyFormatter.format(Number(value))
                                        : numberFormatter.format(Number(value)),
                                    name === 'revenue' ? 'Revenue' : 'Orders',
                                ]}
                            />
                            <Area
                                dataKey="revenue"
                                type="natural"
                                fill="url(#fillReportRevenue)"
                                stroke={chartBlue}
                                strokeWidth={2}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="flex h-[250px] items-center justify-center text-sm text-muted-foreground">
                        No revenue data available
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function StatusDistributionCard({
    data,
}: {
    data: ReportsData['series']['statuses'];
}) {
    const chartData = useMemo(
        () =>
            data?.map((item) => ({
                ...item,
                fill: statusChartColor(item.name),
            })) ?? [],
        [data],
    );

    return (
        <Card className="@container/card rounded-xl shadow-xs">
            <CardHeader>
                <CardTitle>Orders by status</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Workflow distribution in the filtered range
                    </span>
                    <span className="@[540px]/card:hidden">
                        Status distribution
                    </span>
                </CardDescription>
                <CardAction>
                    <Badge variant="outline">
                        {data?.reduce((total, item) => total + item.value, 0) ??
                            0}{' '}
                        orders
                    </Badge>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {chartData.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <BarChart data={chartData}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="name"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={20}
                            />
                            <YAxis
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                                width={36}
                            />
                            <Tooltip
                                {...chartTooltipProps}
                                cursor={false}
                                formatter={(value) => [
                                    numberFormatter.format(Number(value)),
                                    'Orders',
                                ]}
                            />
                            <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                {chartData.map((entry) => (
                                    <Cell
                                        key={entry.name}
                                        fill={entry.fill}
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="flex h-[250px] items-center justify-center text-sm text-muted-foreground">
                        No status data available
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function InventoryStatusCard({ data }: { data: ReportsData['inventory'] }) {
    const chartData = useMemo(
        () =>
            Object.entries(data).map(([key, value]) => ({
                fill: inventoryChartColor(inventoryMetricLabel(key)),
                name: inventoryMetricLabel(key),
                value,
            })),
        [data],
    );

    return (
        <Card className="@container/card rounded-xl shadow-xs">
            <CardHeader>
                <CardTitle>Inventory status</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Product availability and stock risk summary
                    </span>
                    <span className="@[540px]/card:hidden">
                        Product stock summary
                    </span>
                </CardDescription>
                <CardAction>
                    <Badge variant="outline">
                        {numberFormatter.format(data.total_products ?? 0)}{' '}
                        products
                    </Badge>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {chartData.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <BarChart data={chartData}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="name"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={16}
                            />
                            <YAxis
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                                width={36}
                            />
                            <Tooltip
                                {...chartTooltipProps}
                                cursor={false}
                                formatter={(value) => [
                                    numberFormatter.format(Number(value)),
                                    'Products',
                                ]}
                            />
                            <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                {chartData.map((entry) => (
                                    <Cell
                                        key={entry.name}
                                        fill={entry.fill}
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="flex h-[250px] items-center justify-center text-sm text-muted-foreground">
                        No inventory data available
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function ReportsSummaryTable({
    reports,
    type,
}: {
    reports: ReportsData;
    type: ReportType;
}) {
    const rows = useMemo<SummaryRow[]>(
        () =>
            summaryRows(reportLabels[type], reports[type], type === 'revenue'),
        [reports, type],
    );
    const columns = useMemo<ColumnDef<SummaryRow>[]>(
        () => [
            {
                accessorKey: 'section',
                header: 'Section',
                cell: ({ row }) => (
                    <Badge variant="outline" className="text-muted-foreground">
                        {row.original.section}
                    </Badge>
                ),
            },
            {
                accessorKey: 'metric',
                header: 'Metric',
                cell: ({ row }) => (
                    <span className="font-medium">{row.original.metric}</span>
                ),
            },
            {
                accessorKey: 'value',
                header: 'Value',
                cell: ({ row }) => (
                    <span className="font-medium tabular-nums">
                        {row.original.money ? (
                            <MoneyDisplay value={row.original.value} />
                        ) : (
                            numberFormatter.format(row.original.value)
                        )}
                    </span>
                ),
            },
        ],
        [],
    );

    return (
        <div className="flex flex-col gap-4">
            <div>
                <h2 className="text-base font-medium">Report summary</h2>
                <p className="text-sm text-muted-foreground">
                    Detailed {reportLabels[type].toLowerCase()} metrics for the
                    selected report.
                </p>
            </div>
            <DataTable
                data={rows}
                columns={columns}
                getRowId={(row) => row.id}
                emptyTitle="No report metrics found"
            />
        </div>
    );
}

function summaryRows(
    section: string,
    records: Record<string, number>,
    money: boolean,
): SummaryRow[] {
    return Object.entries(records).map(([key, value]) => ({
        id: `${section}-${key}`,
        metric: formatMetricLabel(key),
        money,
        section,
        value,
    }));
}

function isReportType(value?: string | null): value is ReportType {
    return value === 'orders' || value === 'inventory' || value === 'revenue';
}

function inventoryMetricLabel(value: string): string {
    const labels: Record<string, string> = {
        active_products: 'Active',
        inactive_products: 'Inactive',
        low_stock_products: 'Low Stock',
        out_of_stock_products: 'No Stock',
        total_products: 'Total',
    };

    return labels[value] ?? formatMetricLabel(value);
}

function formatMetricLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDateRange(dateFrom: string, dateTo: string): string {
    if (!dateFrom || !dateTo) {
        return 'Current report scope';
    }

    return `${formatDateLabel(dateFrom)} - ${formatDateLabel(dateTo)}`;
}

function formatDateTick(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
    });
}

function formatDateLabel(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
