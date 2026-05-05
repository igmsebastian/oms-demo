import type { LucideIcon } from 'lucide-react';
import { TrendingDown, TrendingUp } from 'lucide-react';
import type { KeyboardEvent, ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type KpiTone = 'neutral' | 'success' | 'warning' | 'destructive' | 'info';

const cardToneClasses: Record<KpiTone, string> = {
    neutral:
        'border-slate-400/20 bg-gradient-to-br from-slate-600 via-slate-800 to-slate-950 text-white',
    success:
        'border-emerald-300/30 bg-gradient-to-br from-emerald-500 via-emerald-700 to-emerald-950 text-white',
    warning:
        'border-amber-300/50 bg-gradient-to-br from-amber-300 via-amber-500 to-orange-700 text-amber-950 dark:from-amber-500 dark:via-amber-700 dark:to-orange-950 dark:text-white',
    destructive:
        'border-red-300/30 bg-gradient-to-br from-red-500 via-red-700 to-red-950 text-white',
    info: 'border-blue-300/30 bg-gradient-to-br from-blue-500 via-blue-700 to-blue-950 text-white',
};

const mutedToneClasses: Record<KpiTone, string> = {
    neutral: 'text-white/70',
    success: 'text-white/75',
    warning: 'text-amber-950/70 dark:text-white/75',
    destructive: 'text-white/75',
    info: 'text-white/75',
};

const iconToneClasses: Record<KpiTone, string> = {
    neutral: 'bg-white/15 text-white ring-white/20',
    success: 'bg-white/15 text-white ring-white/20',
    warning:
        'bg-white/30 text-amber-950 ring-amber-950/10 dark:bg-white/15 dark:text-white dark:ring-white/20',
    destructive: 'bg-white/15 text-white ring-white/20',
    info: 'bg-white/15 text-white ring-white/20',
};

const badgeToneClasses: Record<KpiTone, string> = {
    neutral: 'border-white/25 bg-white/10 text-white',
    success: 'border-white/25 bg-white/10 text-white',
    warning:
        'border-amber-950/15 bg-white/30 text-amber-950 dark:border-white/25 dark:bg-white/10 dark:text-white',
    destructive: 'border-white/25 bg-white/10 text-white',
    info: 'border-white/25 bg-white/10 text-white',
};

const asteriskToneClasses: Record<KpiTone, string> = {
    neutral: 'text-white/15',
    success: 'text-white/20',
    warning: 'text-amber-950/15 dark:text-white/15',
    destructive: 'text-white/20',
    info: 'text-white/20',
};

type KpiCardProps = {
    title: string;
    value: ReactNode;
    icon: LucideIcon;
    trend?: number | null;
    footerTitle?: string;
    footerDescription?: string;
    tone?: KpiTone;
    className?: string;
    onClick?: () => void;
};

export function KpiCard({
    title,
    value,
    icon: Icon,
    trend,
    footerTitle,
    footerDescription,
    tone = 'neutral',
    className,
    onClick,
}: KpiCardProps) {
    const TrendIcon = (trend ?? 0) >= 0 ? TrendingUp : TrendingDown;
    const hasFooter = Boolean(footerTitle || footerDescription);

    function handleKeyDown(event: KeyboardEvent<HTMLDivElement>) {
        if (!onClick) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onClick();
        }
    }

    return (
        <Card
            className={cn(
                '@container/card relative overflow-hidden rounded-lg border py-6 shadow-sm transition duration-200',
                cardToneClasses[tone],
                onClick &&
                    'cursor-pointer hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                className,
            )}
            onClick={onClick}
            onKeyDown={handleKeyDown}
            role={onClick ? 'button' : undefined}
            tabIndex={onClick ? 0 : undefined}
        >
            <div
                aria-hidden="true"
                className={cn(
                    'pointer-events-none absolute -right-3 -bottom-14 text-[10rem] leading-none font-black tracking-normal select-none',
                    asteriskToneClasses[tone],
                )}
            >
                *
            </div>
            <CardHeader className="relative z-10">
                <CardDescription className={mutedToneClasses[tone]}>
                    {title}
                </CardDescription>
                <CardTitle className="truncate text-2xl font-semibold tracking-normal tabular-nums @[250px]/card:text-3xl">
                    {value}
                </CardTitle>
                <CardAction>
                    {trend !== undefined && trend !== null ? (
                        <Badge
                            variant="outline"
                            className={cn('gap-1', badgeToneClasses[tone])}
                        >
                            <TrendIcon className="size-3" />
                            {trend >= 0 ? '+' : '-'}
                            {Math.abs(trend)}%
                        </Badge>
                    ) : (
                        <div
                            className={cn(
                                'flex size-10 shrink-0 items-center justify-center rounded-xl ring-1',
                                iconToneClasses[tone],
                            )}
                        >
                            <Icon className="size-5" />
                        </div>
                    )}
                </CardAction>
            </CardHeader>

            {hasFooter && (
                <CardFooter className="relative z-10 flex-col items-start gap-1.5 text-sm">
                    {footerTitle && (
                        <div className="line-clamp-1 flex gap-2 font-medium">
                            {footerTitle}
                            {trend !== undefined && trend !== null && (
                                <TrendIcon className="size-4" />
                            )}
                        </div>
                    )}
                    {footerDescription && (
                        <div className={mutedToneClasses[tone]}>
                            {footerDescription}
                        </div>
                    )}
                </CardFooter>
            )}
        </Card>
    );
}
