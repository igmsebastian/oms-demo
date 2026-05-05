import { PackageOpen } from 'lucide-react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    title: string;
    description?: string;
    className?: string;
};

export function EmptyState({ title, description, className }: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-8 text-center', className)}>
            <PackageOpen className="size-8 text-muted-foreground" />
            <p className="font-medium">{title}</p>
            {description && <p className="max-w-sm text-sm text-muted-foreground">{description}</p>}
        </div>
    );
}
