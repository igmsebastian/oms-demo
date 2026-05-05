import * as React from 'react';

import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { errorMessages } from '@/shared/forms/errors';

function Field({
    className,
    orientation = 'vertical',
    ...props
}: React.ComponentProps<'div'> & {
    orientation?: 'vertical' | 'horizontal';
}) {
    return (
        <div
            data-slot="field"
            data-orientation={orientation}
            className={cn(
                'group/field grid gap-2 data-[orientation=horizontal]:flex data-[orientation=horizontal]:items-center data-[orientation=horizontal]:gap-3',
                className,
            )}
            {...props}
        />
    );
}

function FieldGroup({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="field-group"
            className={cn('grid gap-4', className)}
            {...props}
        />
    );
}

function FieldLabel({
    className,
    ...props
}: React.ComponentProps<typeof Label>) {
    return (
        <Label
            data-slot="field-label"
            className={cn(
                'group-data-[invalid=true]/field:text-destructive',
                className,
            )}
            {...props}
        />
    );
}

function FieldDescription({
    className,
    ...props
}: React.ComponentProps<'p'>) {
    return (
        <p
            data-slot="field-description"
            className={cn('text-sm text-muted-foreground', className)}
            {...props}
        />
    );
}

function FieldError({
    className,
    errors,
    children,
    ...props
}: React.ComponentProps<'div'> & {
    errors?: unknown[];
}) {
    const messages = children ? [] : errorMessages(errors);

    if (!children && messages.length === 0) {
        return null;
    }

    return (
        <div
            data-slot="field-error"
            className={cn('text-sm font-medium text-destructive', className)}
            {...props}
        >
            {children ??
                messages.map((message) => <p key={message}>{message}</p>)}
        </div>
    );
}

export { Field, FieldDescription, FieldError, FieldGroup, FieldLabel };
