import { useRemember } from '@inertiajs/react';
import { format, formatDistanceToNow } from 'date-fns';
import { Check, ChevronDown, CircleDot, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { OrderActivity } from '@/entities/order/model/types';
import { cn } from '@/lib/utils';
import { statusKey } from '@/shared/status/statusTheme';

type OrderTimelineProps = {
    activities: OrderActivity[];
    currentStatus: string;
    orderNumber: string;
};

type TimelineStep = {
    status: string;
    label: string;
    description: string;
};

type StepState = 'complete' | 'current' | 'future' | 'danger';

const primaryStatuses = [
    'pending',
    'confirmed',
    'processing',
    'packed',
    'shipped',
    'delivered',
    'completed',
] as const;

const refundStatuses = ['refund_pending', 'refunded'] as const;

const cancellationStatuses = [
    'cancellation_requested',
    'partially_cancelled',
    'cancelled',
] as const;

const timelineSteps: TimelineStep[] = [
    {
        status: 'pending',
        label: 'Pending',
        description: 'Order has been created and is waiting for review.',
    },
    {
        status: 'confirmed',
        label: 'Confirmed',
        description: 'Payment and inventory checks have passed.',
    },
    {
        status: 'processing',
        label: 'Processing',
        description: 'Warehouse preparation is in progress.',
    },
    {
        status: 'packed',
        label: 'Packed',
        description: 'Items are packed and ready for shipment.',
    },
    {
        status: 'shipped',
        label: 'Shipped',
        description: 'Package is already with the courier.',
    },
    {
        status: 'delivered',
        label: 'Delivered',
        description: 'Package has arrived with the customer.',
    },
    {
        status: 'completed',
        label: 'Completed',
        description: 'Order lifecycle is closed.',
    },
    {
        status: 'refund_pending',
        label: 'Refund Pending',
        description: 'Refund is queued for processing.',
    },
    {
        status: 'refunded',
        label: 'Refunded',
        description: 'Refund has been completed.',
    },
    {
        status: 'cancellation_requested',
        label: 'Cancellation Requested',
        description: 'Cancellation request is under review.',
    },
    {
        status: 'partially_cancelled',
        label: 'Partially Cancelled',
        description: 'Some items were removed from the order.',
    },
    {
        status: 'cancelled',
        label: 'Cancelled',
        description: 'Order has been cancelled.',
    },
];

const stateClasses: Record<
    StepState,
    {
        marker: string;
        line: string;
        title: string;
        meta: string;
        label: string | null;
    }
> = {
    complete: {
        marker: 'bg-green-600 text-white ring-4 ring-green-500/15 shadow-lg shadow-green-500/20',
        line: 'bg-green-200 dark:bg-green-900',
        title: 'text-foreground',
        meta: 'text-green-700 dark:text-green-300',
        label: 'Completed',
    },
    current: {
        marker: 'bg-blue-600 text-white ring-4 ring-blue-500/15 shadow-lg shadow-blue-500/30',
        line: 'bg-blue-200 dark:bg-blue-900',
        title: 'text-blue-700 dark:text-blue-300',
        meta: 'text-blue-700 dark:text-blue-300',
        label: 'Current status',
    },
    future: {
        marker: 'bg-muted text-muted-foreground ring-1 ring-border',
        line: 'bg-border',
        title: 'text-muted-foreground',
        meta: 'text-muted-foreground',
        label: null,
    },
    danger: {
        marker: 'bg-red-600 text-white ring-4 ring-red-500/15 shadow-lg shadow-red-500/25',
        line: 'bg-red-200 dark:bg-red-900',
        title: 'text-red-700 dark:text-red-300',
        meta: 'text-red-700 dark:text-red-300',
        label: 'Cancelled',
    },
};

export function OrderTimeline({
    activities,
    currentStatus,
    orderNumber,
}: OrderTimelineProps) {
    const [open, setOpen] = useRemember(
        false,
        `orders.${orderNumber}.progress-timeline.open`,
    );
    const currentStatusKey = statusKey(currentStatus);
    const { reachedStatuses, statusDates, cancellationAnchorStatus } =
        buildTimelineState(activities, currentStatusKey);
    const currentStep = timelineSteps.find(
        (step) => step.status === currentStatusKey,
    );
    const currentDate = formatTimelineDate(statusDates.get(currentStatusKey));

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card className="rounded-lg shadow-none">
                <CardHeader className="pb-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                            <CardTitle>Progress Timeline</CardTitle>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Current status:{' '}
                                <span className="font-medium text-foreground">
                                    {currentStep?.label ?? currentStatus}
                                </span>
                                {currentDate && (
                                    <>
                                        {' '}
                                        -{' '}
                                        <time
                                            dateTime={
                                                statusDates.get(
                                                    currentStatusKey,
                                                ) ?? undefined
                                            }
                                            title={currentDate.exact}
                                        >
                                            {currentDate.relative}
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
                                {open ? 'Hide timeline' : 'View timeline'}
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
                    <CardContent className="pt-0">
                        <ol className="relative">
                            {timelineSteps.map((step, index) => {
                                const state = getStepState(
                                    step.status,
                                    currentStatusKey,
                                    reachedStatuses,
                                    cancellationAnchorStatus,
                                );
                                const classes = stateClasses[state];
                                const date = formatTimelineDate(
                                    statusDates.get(step.status),
                                );

                                return (
                                    <li
                                        key={step.status}
                                        className="relative grid grid-cols-[2.5rem_1fr] gap-3 pb-5 last:pb-0"
                                    >
                                        {index < timelineSteps.length - 1 && (
                                            <span
                                                className={cn(
                                                    'absolute top-10 bottom-0 left-5 w-px',
                                                    classes.line,
                                                )}
                                            />
                                        )}
                                        <div className="relative z-10 flex justify-center">
                                            <div
                                                className={cn(
                                                    'flex size-10 items-center justify-center rounded-full',
                                                    classes.marker,
                                                )}
                                            >
                                                <StepIcon state={state} />
                                            </div>
                                        </div>
                                        <div className="min-w-0 border-b pb-5 last:border-0 last:pb-0">
                                            <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                                <div className="min-w-0">
                                                    <p
                                                        className={cn(
                                                            'font-medium',
                                                            classes.title,
                                                        )}
                                                    >
                                                        {step.label}
                                                    </p>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {step.description}
                                                    </p>
                                                    {classes.label && (
                                                        <p
                                                            className={cn(
                                                                'mt-2 text-xs font-medium',
                                                                classes.meta,
                                                            )}
                                                        >
                                                            {classes.label}
                                                        </p>
                                                    )}
                                                </div>
                                                {date ? (
                                                    <time
                                                        dateTime={
                                                            statusDates.get(
                                                                step.status,
                                                            ) ?? undefined
                                                        }
                                                        title={date.exact}
                                                        className="shrink-0 text-xs text-muted-foreground"
                                                    >
                                                        {date.relative}
                                                    </time>
                                                ) : (
                                                    <span className="shrink-0 text-xs text-muted-foreground">
                                                        -
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}

function StepIcon({ state }: { state: StepState }) {
    if (state === 'complete') {
        return <Check className="size-4" />;
    }

    if (state === 'current') {
        return <CircleDot className="size-4" />;
    }

    if (state === 'danger') {
        return <X className="size-4" />;
    }

    return <span className="size-2.5 rounded-full bg-current" />;
}

function buildTimelineState(
    activities: OrderActivity[],
    currentStatusKey: string,
) {
    const reachedStatuses = new Set<string>();
    const statusDates = new Map<string, string>();
    const sortedActivities = [...activities].sort(
        (first, second) =>
            new Date(first.created_at).getTime() -
            new Date(second.created_at).getTime(),
    );

    reachedStatuses.add('pending');
    reachedStatuses.add(currentStatusKey);

    sortedActivities.forEach((activity) => {
        const fromStatus = normalizeStatus(activity.from_status);
        const toStatus = normalizeStatus(activity.to_status);

        if (fromStatus) {
            reachedStatuses.add(fromStatus);
        }

        if (toStatus) {
            reachedStatuses.add(toStatus);
            statusDates.set(toStatus, activity.created_at);
        }
    });

    addPreviousStatuses(currentStatusKey, reachedStatuses);

    const cancellationAnchorStatus =
        currentStatusKey === 'cancelled'
            ? latestPrimaryStatus(reachedStatuses)
            : null;

    if (cancellationAnchorStatus) {
        addPreviousStatuses(cancellationAnchorStatus, reachedStatuses);
    }

    return {
        reachedStatuses,
        statusDates,
        cancellationAnchorStatus,
    };
}

function addPreviousStatuses(
    currentStatusKey: string,
    reachedStatuses: Set<string>,
) {
    const primaryIndex = primaryStatuses.indexOf(
        currentStatusKey as (typeof primaryStatuses)[number],
    );
    const refundIndex = refundStatuses.indexOf(
        currentStatusKey as (typeof refundStatuses)[number],
    );
    const cancellationIndex = cancellationStatuses.indexOf(
        currentStatusKey as (typeof cancellationStatuses)[number],
    );

    if (primaryIndex >= 0) {
        primaryStatuses
            .slice(0, primaryIndex)
            .forEach((status) => reachedStatuses.add(status));
    }

    if (refundIndex >= 0) {
        primaryStatuses.forEach((status) => reachedStatuses.add(status));
        refundStatuses
            .slice(0, refundIndex)
            .forEach((status) => reachedStatuses.add(status));
    }

    if (cancellationIndex >= 0) {
        cancellationStatuses
            .slice(0, cancellationIndex)
            .forEach((status) => reachedStatuses.add(status));
    }
}

function getStepState(
    stepStatus: string,
    currentStatusKey: string,
    reachedStatuses: Set<string>,
    cancellationAnchorStatus: string | null,
): StepState {
    if (currentStatusKey === 'cancelled' && stepStatus === 'cancelled') {
        return 'danger';
    }

    if (
        currentStatusKey === 'cancelled' &&
        cancellationAnchorStatus === stepStatus
    ) {
        return 'current';
    }

    if (stepStatus === currentStatusKey) {
        return 'current';
    }

    if (reachedStatuses.has(stepStatus)) {
        return 'complete';
    }

    return 'future';
}

function latestPrimaryStatus(reachedStatuses: Set<string>): string | null {
    return (
        [...primaryStatuses]
            .reverse()
            .find((status) => reachedStatuses.has(status)) ?? null
    );
}

function normalizeStatus(status?: string | null): string | null {
    if (!status) {
        return null;
    }

    return statusKey(status);
}

function formatTimelineDate(
    value?: string,
): { relative: string; exact: string } | null {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return {
        relative: formatDistanceToNow(date, { addSuffix: true }),
        exact: format(date, 'MMM d, yyyy, h:mm a'),
    };
}
