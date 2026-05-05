import { router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { Pencil, Plus, Trash } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { ProductTaxonomy } from '@/entities/product/model/types';
import { destroy, store, update } from '@/routes/product-management';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';
import { fieldErrors } from '@/shared/forms/errors';
import type { ServerFormErrors } from '@/shared/forms/errors';

const crudSchema = z.object({
    name: z
        .string()
        .min(1, 'Enter a name.')
        .max(255, 'Name may not be longer than 255 characters.'),
    description: z.string(),
});

type CrudFormData = z.infer<typeof crudSchema>;

type CrudDialogFormProps = {
    module: string;
    record?: ProductTaxonomy;
    mode: 'create' | 'edit' | 'delete';
    autoOpen?: boolean;
    onClose?: () => void;
};

export function CrudDialogForm({
    module,
    record,
    mode,
    autoOpen = false,
    onClose,
}: CrudDialogFormProps) {
    const [open, setOpen] = useState(autoOpen);
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const persist = (value: CrudFormData) => {
        setProcessing(true);
        const request =
            mode === 'create' || !record
                ? store.post(module)
                : update.patch({ module, record: record.id });

        router.visit(request.url, {
            method: request.method,
            data: value,
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                onClose?.();
            },
            onError: (backendErrors) => {
                setServerErrors(backendErrors);
                toast.error(
                    'We could not save this record. Please review the form and try again.',
                );
            },
            onFinish: () => setProcessing(false),
        });
    };
    const form = useForm({
        defaultValues: {
            name: record?.name ?? '',
            description: record?.description ?? '',
        },
        validators: {
            onSubmit: crudSchema,
        },
        onSubmit: ({ value }) => {
            setServerErrors({});
            persist(value);
        },
    });

    if (mode === 'delete' && record) {
        return (
            <>
                {!autoOpen && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive"
                        onClick={() => setOpen(true)}
                    >
                        <Trash className="size-4" />
                        Delete
                    </Button>
                )}
                <ConfirmationDialog
                    open={open}
                    onOpenChange={(value) => {
                        setOpen(value);

                        if (!value) {
                            onClose?.();
                        }
                    }}
                    title="Delete record"
                    description={`Delete ${record.name}.`}
                    confirmLabel="Delete"
                    destructive
                    processing={processing}
                    showNote={false}
                    onConfirm={() => {
                        setProcessing(true);
                        const request = destroy.delete({
                            module,
                            record: record.id,
                        });
                        router.visit(request.url, {
                            method: request.method,
                            preserveScroll: true,
                            onError: () =>
                                toast.error(
                                    'We could not delete this record. Please try again.',
                                ),
                            onFinish: () => {
                                setProcessing(false);
                                setOpen(false);
                                onClose?.();
                            },
                        });
                    }}
                />
            </>
        );
    }

    const icon = mode === 'create' ? Plus : Pencil;
    const Icon = icon;

    return (
        <>
            {!autoOpen && (
                <Button
                    variant={mode === 'create' ? 'default' : 'ghost'}
                    size="sm"
                    onClick={() => setOpen(true)}
                >
                    <Icon className="size-4" />
                    {mode === 'create' ? 'Create' : 'Edit'}
                </Button>
            )}
            <Dialog
                open={open}
                onOpenChange={(value) => {
                    setOpen(value);

                    if (!value) {
                        onClose?.();
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create'
                                ? 'Create record'
                                : `Edit ${record?.name}`}
                        </DialogTitle>
                    </DialogHeader>
                    <form
                        id={`taxonomy-${module}-${mode}-form`}
                        onSubmit={(event) => {
                            event.preventDefault();
                            void form.handleSubmit();
                        }}
                    >
                        <FieldGroup>
                            <form.Field
                                name="name"
                                children={(field) => {
                                    const errors = fieldErrors(
                                        field.state.meta.errors,
                                        serverErrors.name,
                                    );
                                    const isInvalid = errors.length > 0;

                                    return (
                                        <Field data-invalid={isInvalid}>
                                            <FieldLabel
                                                htmlFor={field.name}
                                                required
                                            >
                                                Name
                                            </FieldLabel>
                                            <Input
                                                id={field.name}
                                                name={field.name}
                                                value={field.state.value}
                                                onBlur={field.handleBlur}
                                                onChange={(event) =>
                                                    field.handleChange(
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={isInvalid}
                                            />
                                            <FieldError errors={errors} />
                                        </Field>
                                    );
                                }}
                            />
                            <form.Field
                                name="description"
                                children={(field) => {
                                    const errors = fieldErrors(
                                        field.state.meta.errors,
                                        serverErrors.description,
                                    );
                                    const isInvalid = errors.length > 0;

                                    return (
                                        <Field data-invalid={isInvalid}>
                                            <FieldLabel htmlFor={field.name}>
                                                Description
                                            </FieldLabel>
                                            <Input
                                                id={field.name}
                                                name={field.name}
                                                value={field.state.value ?? ''}
                                                onBlur={field.handleBlur}
                                                onChange={(event) =>
                                                    field.handleChange(
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={isInvalid}
                                            />
                                            <FieldError errors={errors} />
                                        </Field>
                                    );
                                }}
                            />
                        </FieldGroup>
                    </form>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setOpen(false);
                                onClose?.();
                            }}
                            disabled={processing}
                        >
                            Close
                        </Button>
                        <Button
                            type="submit"
                            form={`taxonomy-${module}-${mode}-form`}
                            disabled={processing}
                        >
                            Save
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
