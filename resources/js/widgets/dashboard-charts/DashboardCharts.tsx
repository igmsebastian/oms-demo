import type { ColumnDef } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { DashboardData } from '@/entities/report/model/types';
import { useIsMobile } from '@/hooks/use-mobile';
import {
    chartBlue,
    chartTooltipProps,
    statusChartColor,
} from '@/shared/charts/chartTheme';
import { DataTable } from '@/shared/components/DataTable';

type DashboardChartsProps = {
    data: DashboardData;
};

type TimeRange = '12m' | '6m' | '3m';

type RevenuePoint = DashboardData['revenue_series'][number] & {
    date: Date;
};

type StatusRow = {
    name: string;
    value: number;
    percentage: number;
    color: string;
};

const timeRangeLabels: Record<TimeRange, string> = {
    '12m': 'Last 12 months',
    '6m': 'Last 6 months',
    '3m': 'Last 3 months',
};

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

export function DashboardCharts({ data }: DashboardChartsProps) {
    const statusRows = useMemo<StatusRow[]>(() => {
        const total = data.status_chart.reduce(
            (sum, item) => sum + item.value,
            0,
        );

        return data.status_chart.map((item) => ({
            ...item,
            color: statusChartColor(item.name),
            percentage: total > 0 ? Math.round((item.value / total) * 100) : 0,
        }));
    }, [data.status_chart]);

    const statusColumns = useMemo<ColumnDef<StatusRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Status',
                cell: ({ row }) => (
                    <div className="flex items-center gap-2">
                        <span
                            className="size-2 rounded-full"
                            style={{ backgroundColor: row.original.color }}
                        />
                        <span className="font-medium">{row.original.name}</span>
                    </div>
                ),
            },
            {
                accessorKey: 'value',
                header: 'Orders',
                cell: ({ row }) => (
                    <span className="font-medium tabular-nums">
                        {row.original.value}
                    </span>
                ),
            },
            {
                id: 'share',
                header: 'Share',
                cell: ({ row }) => (
                    <div className="flex min-w-48 items-center gap-3">
                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full"
                                style={{
                                    backgroundColor: row.original.color,
                                    width: `${row.original.percentage}%`,
                                }}
                            />
                        </div>
                        <span className="w-10 text-right text-sm text-muted-foreground tabular-nums">
                            {row.original.percentage}%
                        </span>
                    </div>
                ),
            },
        ],
        [],
    );

    return (
        <div className="space-y-4 md:space-y-6">
            <RevenueAreaChart data={data.revenue_series} />

            <div className="space-y-3">
                <div>
                    <h2 className="text-base font-medium">Orders by status</h2>
                    <p className="text-sm text-muted-foreground">
                        Current order distribution across the workflow.
                    </p>
                </div>

                <DataTable
                    data={statusRows}
                    columns={statusColumns}
                    emptyTitle="No order statuses found"
                />
            </div>
        </div>
    );
}

function RevenueAreaChart({ data }: { data: DashboardData['revenue_series'] }) {
    const isMobile = useIsMobile();
    const [timeRange, setTimeRange] = useState<TimeRange>(
        isMobile ? '3m' : '12m',
    );

    const chartData = useMemo<RevenuePoint[]>(
        () =>
            data.map((item) => ({
                ...item,
                date: periodToDate(item.period),
            })),
        [data],
    );

    const filteredData = useMemo(() => {
        if (chartData.length === 0) {
            return [];
        }

        const referenceDate = chartData.reduce(
            (latest, item) => (item.date > latest ? item.date : latest),
            chartData[0].date,
        );
        const startDate = new Date(referenceDate);
        const monthsToShow =
            timeRange === '12m' ? 12 : timeRange === '6m' ? 6 : 3;

        startDate.setMonth(startDate.getMonth() - monthsToShow + 1);

        return chartData.filter((item) => item.date >= startDate);
    }, [chartData, timeRange]);

    return (
        <Card className="@container/card rounded-xl shadow-xs">
            <CardHeader>
                <CardTitle>Revenue over time</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Revenue and order volume for{' '}
                        {timeRangeLabels[timeRange].toLowerCase()}
                    </span>
                    <span className="@[540px]/card:hidden">
                        {timeRangeLabels[timeRange]}
                    </span>
                </CardDescription>
                <CardAction>
                    <ToggleGroup
                        type="single"
                        value={timeRange}
                        onValueChange={(value) => {
                            if (value) {
                                setTimeRange(value as TimeRange);
                            }
                        }}
                        variant="outline"
                        className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
                    >
                        <ToggleGroupItem value="12m">
                            Last 12 months
                        </ToggleGroupItem>
                        <ToggleGroupItem value="6m">
                            Last 6 months
                        </ToggleGroupItem>
                        <ToggleGroupItem value="3m">
                            Last 3 months
                        </ToggleGroupItem>
                    </ToggleGroup>
                    <Select
                        value={timeRange}
                        onValueChange={(value) =>
                            setTimeRange(value as TimeRange)
                        }
                    >
                        <SelectTrigger
                            className="flex w-40 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
                            size="sm"
                            aria-label="Select revenue range"
                        >
                            <SelectValue placeholder="Last 12 months" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl">
                            <SelectItem value="12m" className="rounded-lg">
                                Last 12 months
                            </SelectItem>
                            <SelectItem value="6m" className="rounded-lg">
                                Last 6 months
                            </SelectItem>
                            <SelectItem value="3m" className="rounded-lg">
                                Last 3 months
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {filteredData.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <AreaChart data={filteredData}>
                            <defs>
                                <linearGradient
                                    id="fillRevenue"
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
                                dataKey="period"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={32}
                                tickFormatter={formatPeriod}
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
                                    formatPeriod(String(value))
                                }
                                formatter={(value, name) => [
                                    name === 'revenue'
                                        ? moneyFormatter.format(Number(value))
                                        : value,
                                    name === 'revenue' ? 'Revenue' : 'Orders',
                                ]}
                            />
                            <Area
                                dataKey="revenue"
                                type="natural"
                                fill="url(#fillRevenue)"
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

function periodToDate(period: string): Date {
    const [year, month] = period.split('-').map(Number);

    return new Date(year, (month || 1) - 1, 1);
}

function formatPeriod(period: string): string {
    const date = periodToDate(period);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}
