import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type KpiGridProps = {
    children: ReactNode;
    className?: string;
};

export function KpiGrid({ children, className }: KpiGridProps) {
    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4',
                className,
            )}
        >
            {children}
        </div>
    );
}
