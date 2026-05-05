import type { ReactNode } from 'react';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { fieldErrors } from '@/shared/forms/errors';

export type TanStackFieldApi<TValue> = {
    name: string;
    state: {
        value: TValue;
        meta: {
            errors: unknown[];
        };
    };
    handleBlur: () => void;
    handleChange: (value: TValue) => void;
};

type TanStackFieldProps<TValue> = {
    field: TanStackFieldApi<TValue>;
    label: string;
    serverError?: string;
    required?: boolean;
    className?: string;
    children: (props: {
        id: string;
        name: string;
        value: TValue;
        isInvalid: boolean;
        onBlur: () => void;
        onChange: (value: TValue) => void;
    }) => ReactNode;
};

export function TanStackField<TValue>({
    field,
    label,
    serverError,
    required = false,
    className,
    children,
}: TanStackFieldProps<TValue>) {
    const errors = fieldErrors(field.state.meta.errors, serverError);
    const isInvalid = errors.length > 0;

    return (
        <Field data-invalid={isInvalid} className={className}>
            <FieldLabel htmlFor={field.name} required={required}>
                {label}
            </FieldLabel>
            {children({
                id: field.name,
                name: field.name,
                value: field.state.value,
                isInvalid,
                onBlur: field.handleBlur,
                onChange: field.handleChange,
            })}
            <FieldError errors={errors} />
        </Field>
    );
}
