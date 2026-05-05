import { useForm } from '@tanstack/react-form';
import { useEffect, useId, useMemo } from 'react';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { fieldErrors } from '@/shared/forms/errors';

type ConfirmationDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    confirmLabel: string;
    destructive?: boolean;
    noteLabel?: string;
    notePlaceholder?: string;
    requireNote?: boolean;
    showNote?: boolean;
    processing?: boolean;
    onConfirm: (note: string) => void;
};

export function ConfirmationDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    destructive,
    noteLabel = 'Note',
    notePlaceholder = 'Optional note',
    requireNote,
    showNote = true,
    processing,
    onConfirm,
}: ConfirmationDialogProps) {
    const formId = useId();
    const schema = useMemo(
        () =>
            z.object({
                note: requireNote
                    ? z
                          .string()
                          .trim()
                          .min(1, `Enter ${noteLabel.toLowerCase()}.`)
                          .max(
                              5000,
                              `${noteLabel} may not be longer than 5000 characters.`,
                          )
                    : z
                          .string()
                          .max(
                              5000,
                              `${noteLabel} may not be longer than 5000 characters.`,
                          ),
            }),
        [noteLabel, requireNote],
    );
    const form = useForm({
        defaultValues: {
            note: '',
        },
        validators: {
            onSubmit: schema,
        },
        onSubmit: ({ value }) => onConfirm(showNote ? value.note : ''),
    });

    useEffect(() => {
        if (!open) {
            form.reset();
        }
    }, [form, open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                <form
                    id={formId}
                    onSubmit={(event) => {
                        event.preventDefault();
                        void form.handleSubmit();
                    }}
                >
                    {showNote && (
                        <form.Field
                            name="note"
                            children={(field) => {
                                const errors = fieldErrors(
                                    field.state.meta.errors,
                                );
                                const isInvalid = errors.length > 0;

                                return (
                                    <Field data-invalid={isInvalid}>
                                        <FieldLabel
                                            htmlFor={field.name}
                                            required={requireNote}
                                        >
                                            {noteLabel}
                                        </FieldLabel>
                                        <textarea
                                            id={field.name}
                                            name={field.name}
                                            value={field.state.value}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    event.target.value,
                                                )
                                            }
                                            maxLength={5000}
                                            placeholder={notePlaceholder}
                                            aria-invalid={isInvalid}
                                            className="min-h-24 rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                        />
                                        <FieldError errors={errors} />
                                    </Field>
                                );
                            }}
                        />
                    )}
                </form>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        Close
                    </Button>
                    <Button
                        type="submit"
                        form={formId}
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={processing}
                    >
                        {processing ? 'Working...' : confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
