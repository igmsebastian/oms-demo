import { useRemember } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { format, formatDistanceToNow } from 'date-fns';
import { ArrowRight, ChevronDown } from 'lucide-react';
import { useMemo } from 'react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { OrderActivity } from '@/entities/order/model/types';
import { cn } from '@/lib/utils';
import { DataTable } from '@/shared/components/DataTable';
import { statusColor } from '@/shared/status/statusTheme';

type OrderActivityTableProps = {
    activities: OrderActivity[];
    orderNumber: string;
    composer?: ReactNode;
};

export function OrderActivityTable({
    activities,
    orderNumber,
    composer,
}: OrderActivityTableProps) {
    const [open, setOpen] = useRemember(
        false,
        `orders.${orderNumber}.activity-logs.open`,
    );
    const sortedActivities = useMemo(
        () =>
            [...activities].sort(
                (first, second) =>
                    new Date(second.created_at).getTime() -
                    new Date(first.created_at).getTime(),
            ),
        [activities],
    );
    const latestActivity = sortedActivities[0];
    const latestActivityDate = latestActivity
        ? formatActivityDate(latestActivity.created_at)
        : null;
    const columns = useMemo<ColumnDef<OrderActivity>[]>(
        () => [
            {
                accessorKey: 'title',
                header: 'Note / Action',
                cell: ({ row }) => (
                    <div className="max-w-[34rem] min-w-64 break-words whitespace-normal">
                        <p className="font-medium">{row.original.title}</p>
                        {row.original.description && (
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                {row.original.description}
                            </p>
                        )}
                    </div>
                ),
            },
            {
                id: 'actor',
                header: 'Actor',
                cell: ({ row }) =>
                    row.original.actor?.name ? (
                        <span className="whitespace-nowrap">
                            {row.original.actor.name}
                        </span>
                    ) : (
                        'System'
                    ),
            },
            {
                id: 'transition',
                header: 'Transition',
                enableSorting: false,
                cell: ({ row }) => (
                    <TransitionCell
                        fromStatus={row.original.from_status}
                        toStatus={row.original.to_status}
                    />
                ),
            },
            {
                accessorKey: 'created_at',
                header: 'Date',
                cell: ({ row }) => (
                    <ActivityDate value={row.original.created_at} />
                ),
            },
        ],
        [],
    );

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card className="rounded-lg shadow-none">
                <CardHeader className="pb-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                            <CardTitle>Activity Logs</CardTitle>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {sortedActivities.length}{' '}
                                {sortedActivities.length === 1
                                    ? 'entry'
                                    : 'entries'}
                                {latestActivity && latestActivityDate && (
                                    <>
                                        {' '}
                                        - latest:{' '}
                                        <span className="font-medium text-foreground">
                                            {latestActivity.title}
                                        </span>{' '}
                                        <time
                                            dateTime={latestActivity.created_at}
                                            title={latestActivityDate.exact}
                                        >
                                            {latestActivityDate.relative}
                                        </time>
                                    </>
                                )}
                            </p>
                        </div>
                        <CollapsibleTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="w-full justify-between sm:w-fit"
                            >
                                {open ? 'Hide activity' : 'View activity'}
                                <ChevronDown
                                    className={cn(
                                        'size-4 transition-transform',
                                        open && 'rotate-180',
                                    )}
                                />
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent className="space-y-4 pt-0">
                        {composer}
                        <DataTable
                            data={sortedActivities}
                            columns={columns}
                            enableColumnVisibility={false}
                            emptyTitle="No activity found"
                        />
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}

function TransitionCell({
    fromStatus,
    toStatus,
}: {
    fromStatus?: string | null;
    toStatus?: string | null;
}) {
    if (!fromStatus && !toStatus) {
        return <span className="text-muted-foreground">-</span>;
    }

    return (
        <div className="flex flex-nowrap items-center gap-1.5 whitespace-nowrap">
            <TransitionStatusBadge status={fromStatus ?? 'new'} />
            <ArrowRight className="size-3.5 text-muted-foreground" />
            <TransitionStatusBadge status={toStatus ?? 'unknown'} />
        </div>
    );
}

function TransitionStatusBadge({ status }: { status: string }) {
    const color = statusColor(status);

    return (
        <Badge
            variant="outline"
            className="shrink-0 rounded-md px-2 py-0.5 font-medium whitespace-nowrap"
            style={{
                backgroundColor: `${color}1A`,
                borderColor: `${color}55`,
                color,
            }}
        >
            {formatStatusLabel(status)}
        </Badge>
    );
}

function formatStatusLabel(status: string): string {
    return status.replaceAll('_', ' ');
}

function ActivityDate({ value }: { value: string }) {
    const formattedDate = formatActivityDate(value);

    if (!formattedDate) {
        return <span className="text-muted-foreground">-</span>;
    }

    return (
        <div className="whitespace-nowrap">
            <time
                dateTime={value}
                title={formattedDate.exact}
                className="text-sm font-medium"
            >
                {formattedDate.relative}
            </time>
            <p className="text-xs text-muted-foreground">
                {formattedDate.exact}
            </p>
        </div>
    );
}

function formatActivityDate(
    value: string,
): { relative: string; exact: string } | null {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return {
        relative: formatDistanceToNow(date, { addSuffix: true }),
        exact: format(date, 'MMM d, yyyy, h:mm a'),
    };
}
