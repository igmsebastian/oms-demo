import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { statusTone } from '@/shared/status/statusTheme';
import type { StatusTone } from '@/shared/status/statusTheme';

const classes: Record<StatusTone, string> = {
    neutral: 'border-neutral-200 bg-neutral-100 text-neutral-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-200',
    success: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',
    warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    destructive: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
};

type StatusBadgeProps = {
    status: string;
    label?: string;
    className?: string;
};

export function StatusBadge({ status, label, className }: StatusBadgeProps) {
    const tone = statusTone(status);

    return (
        <Badge
            variant="outline"
            className={cn(
                'rounded-md px-2 py-0.5 font-medium',
                classes[tone],
                className,
            )}
        >
            {label ?? status.replaceAll('_', ' ')}
        </Badge>
    );
}
