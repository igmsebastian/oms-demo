import { router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import {
    cloneElement,
    isValidElement,
    useEffect,
    useRef,
    useState,
} from 'react';
import type { FocusEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { ProductReferenceLists } from '@/entities/product/model/types';
import { store, update } from '@/routes/products';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';
import { fieldErrors } from '@/shared/forms/errors';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { useProductSheetStore } from './useProductSheetStore';

const productSchema = z.object({
    sku: z
        .string()
        .min(1, 'Enter a SKU.')
        .max(255, 'SKU may not be longer than 255 characters.'),
    name: z
        .string()
        .min(1, 'Enter a product name.')
        .max(255, 'Product name may not be longer than 255 characters.'),
    description: z.string(),
    product_category_id: z.string().nullable(),
    product_brand_id: z.string().nullable(),
    product_unit_id: z.string().nullable(),
    product_size_id: z.string().nullable(),
    product_color_id: z.string().nullable(),
    tag_ids: z.array(z.string()),
    price: z.number().min(0, 'Price cannot be negative.'),
    stock_quantity: z
        .number()
        .int('Stock must be a whole number.')
        .min(0, 'Stock cannot be negative.'),
    low_stock_threshold: z
        .number()
        .int('Low stock threshold must be a whole number.')
        .min(0, 'Low stock threshold cannot be negative.'),
    is_active: z.boolean(),
});

type ProductFormData = z.infer<typeof productSchema>;

type ProductFormSheetProps = {
    references: ProductReferenceLists;
};

const emptyForm: ProductFormData = {
    sku: '',
    name: '',
    description: '',
    product_category_id: null,
    product_brand_id: null,
    product_unit_id: null,
    product_size_id: null,
    product_color_id: null,
    tag_ids: [],
    price: 0,
    stock_quantity: 0,
    low_stock_threshold: 5,
    is_active: true,
};

function selectNumericValue(event: FocusEvent<HTMLInputElement>) {
    const input = event.currentTarget;

    window.requestAnimationFrame(() => input.select());
}

export function ProductFormSheet({ references }: ProductFormSheetProps) {
    const { open, product, close } = useProductSheetStore();
    const skuInputRef = useRef<HTMLInputElement>(null);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const [createAnother, setCreateAnother] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [pendingValue, setPendingValue] = useState<ProductFormData | null>(
        null,
    );
    const isEditing = Boolean(product);
    const title = isEditing ? `Edit ${product?.name}` : 'Create Product';
    const form = useForm({
        defaultValues: emptyForm,
        validators: {
            onSubmit: productSchema,
        },
        onSubmit: ({ value }) => {
            setServerErrors({});
            setPendingValue(value);
            setConfirming(true);
        },
    });

    useEffect(() => {
        let ignore = false;
        const clearErrorsAfterReset = () => {
            queueMicrotask(() => {
                if (!ignore) {
                    setServerErrors({});
                }
            });
        };

        if (!product) {
            form.reset(emptyForm);
            clearErrorsAfterReset();

            return () => {
                ignore = true;
            };
        }

        form.reset({
            sku: product.sku,
            name: product.name,
            description: product.description ?? '',
            product_category_id: product.product_category_id ?? null,
            product_brand_id: product.product_brand_id ?? null,
            product_unit_id: product.product_unit_id ?? null,
            product_size_id: product.product_size_id ?? null,
            product_color_id: product.product_color_id ?? null,
            tag_ids:
                product.tag_ids ?? product.tags?.map((tag) => tag.id) ?? [],
            price: Number(product.price),
            stock_quantity: product.stock_quantity,
            low_stock_threshold: product.low_stock_threshold,
            is_active: product.is_active,
        });
        clearErrorsAfterReset();

        return () => {
            ignore = true;
        };
    }, [product, form]);

    const focusSku = () => {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                skuInputRef.current?.focus();
                skuInputRef.current?.select();
            });
        });
    };

    const persist = () => {
        if (!pendingValue) {
            return;
        }

        setProcessing(true);
        const request =
            isEditing && product ? update.patch(product.id) : store.post();
        const shouldCreateAnother = !isEditing && createAnother;
        let saved = false;

        router.visit(request.url, {
            method: request.method,
            data: pendingValue,
            preserveState: true,
            preserveScroll: true,
            onError: (backendErrors) => {
                setServerErrors(backendErrors);
                toast.error(
                    'We could not save the product. Please review the form and try again.',
                );
            },
            onSuccess: () => {
                saved = true;

                if (shouldCreateAnother) {
                    form.reset(emptyForm);
                    setServerErrors({});

                    return;
                }

                close();
            },
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
                setPendingValue(null);

                if (shouldCreateAnother && saved) {
                    focusSku();
                }
            },
        });
    };

    return (
        <>
            <Sheet
                open={open}
                onOpenChange={(value) => (!value ? close() : undefined)}
            >
                <SheetContent className="w-[min(94vw,64rem)] max-w-none gap-0 overflow-hidden sm:max-w-none">
                    <SheetHeader className="px-6 py-5">
                        <SheetTitle>{title}</SheetTitle>
                        <SheetDescription>
                            Maintain product, inventory, and reference data.
                        </SheetDescription>
                    </SheetHeader>
                    <form
                        id="product-form"
                        className="grid gap-4 px-6 pb-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            void form.handleSubmit();
                        }}
                    >
                        <FieldGroup className="grid gap-3 md:grid-cols-12">
                            <form.Field
                                name="sku"
                                children={(field) => (
                                    <TanStackTextField
                                        field={field}
                                        label="SKU"
                                        serverError={serverErrors.sku}
                                        className="md:col-span-6"
                                        required
                                    >
                                        <Input
                                            ref={skuInputRef}
                                            id={field.name}
                                            name={field.name}
                                            value={field.state.value}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </TanStackTextField>
                                )}
                            />
                            <form.Field
                                name="name"
                                children={(field) => (
                                    <TanStackTextField
                                        field={field}
                                        label="Name"
                                        serverError={serverErrors.name}
                                        className="md:col-span-6"
                                        required
                                    >
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
                                        />
                                    </TanStackTextField>
                                )}
                            />
                            <form.Field
                                name="price"
                                children={(field) => (
                                    <TanStackTextField
                                        field={field}
                                        label="Price"
                                        serverError={serverErrors.price}
                                        className="md:col-span-3"
                                        required
                                    >
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={field.state.value}
                                            onFocus={selectNumericValue}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                    </TanStackTextField>
                                )}
                            />
                            <form.Field
                                name="stock_quantity"
                                children={(field) => (
                                    <TanStackTextField
                                        field={field}
                                        label="Stock"
                                        serverError={
                                            serverErrors.stock_quantity
                                        }
                                        className="md:col-span-3"
                                        required
                                    >
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type="number"
                                            min="0"
                                            value={field.state.value}
                                            onFocus={selectNumericValue}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                    </TanStackTextField>
                                )}
                            />
                            <form.Field
                                name="low_stock_threshold"
                                children={(field) => (
                                    <TanStackTextField
                                        field={field}
                                        label="Low Stock Threshold"
                                        serverError={
                                            serverErrors.low_stock_threshold
                                        }
                                        className="md:col-span-4"
                                        required
                                    >
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type="number"
                                            min="0"
                                            value={field.state.value}
                                            onFocus={selectNumericValue}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                    </TanStackTextField>
                                )}
                            />
                            <form.Field
                                name="is_active"
                                children={(field) => (
                                    <Field
                                        orientation="horizontal"
                                        className="md:col-span-2 md:pt-7"
                                    >
                                        <Checkbox
                                            id={field.name}
                                            checked={field.state.value}
                                            onCheckedChange={(checked) =>
                                                field.handleChange(
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor={field.name}>
                                            Active
                                        </Label>
                                    </Field>
                                )}
                            />
                        </FieldGroup>
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
                                            aria-invalid={isInvalid}
                                            className="h-20 resize-none rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                        />
                                        <FieldError errors={errors} />
                                    </Field>
                                );
                            }}
                        />
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <form.Field
                                name="product_category_id"
                                children={(field) => (
                                    <ReferenceSelect
                                        field={field}
                                        label="Category"
                                        records={references.categories}
                                        serverError={
                                            serverErrors.product_category_id
                                        }
                                    />
                                )}
                            />
                            <form.Field
                                name="product_brand_id"
                                children={(field) => (
                                    <ReferenceSelect
                                        field={field}
                                        label="Brand"
                                        records={references.brands}
                                        serverError={
                                            serverErrors.product_brand_id
                                        }
                                    />
                                )}
                            />
                            <form.Field
                                name="product_unit_id"
                                children={(field) => (
                                    <ReferenceSelect
                                        field={field}
                                        label="Unit of Measure"
                                        records={references.units}
                                        serverError={
                                            serverErrors.product_unit_id
                                        }
                                    />
                                )}
                            />
                            <form.Field
                                name="product_size_id"
                                children={(field) => (
                                    <ReferenceSelect
                                        field={field}
                                        label="Size"
                                        records={references.sizes}
                                        serverError={
                                            serverErrors.product_size_id
                                        }
                                    />
                                )}
                            />
                            <form.Field
                                name="product_color_id"
                                children={(field) => (
                                    <ReferenceSelect
                                        field={field}
                                        label="Color"
                                        records={references.colors}
                                        serverError={
                                            serverErrors.product_color_id
                                        }
                                    />
                                )}
                            />
                        </div>
                        <form.Field
                            name="tag_ids"
                            children={(field) => {
                                const selected = field.state.value;
                                const errors = fieldErrors(
                                    field.state.meta.errors,
                                    serverErrors.tag_ids,
                                );
                                const isInvalid = errors.length > 0;

                                return (
                                    <Field data-invalid={isInvalid}>
                                        <FieldLabel>Tags</FieldLabel>
                                        <div
                                            aria-invalid={isInvalid}
                                            className="grid gap-x-4 gap-y-2 rounded-md border p-3 aria-invalid:border-destructive aria-invalid:ring-destructive/20 sm:grid-cols-2 lg:grid-cols-4"
                                        >
                                            {references.tags.map((tag) => (
                                                <label
                                                    key={tag.id}
                                                    className="flex min-w-0 items-center gap-2 text-sm"
                                                >
                                                    <Checkbox
                                                        checked={selected.includes(
                                                            tag.id,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) => {
                                                            field.handleChange(
                                                                checked === true
                                                                    ? [
                                                                          ...selected,
                                                                          tag.id,
                                                                      ]
                                                                    : selected.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              tag.id,
                                                                      ),
                                                            );
                                                        }}
                                                    />
                                                    <span className="truncate">
                                                        {tag.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        <FieldError errors={errors} />
                                    </Field>
                                );
                            }}
                        />
                    </form>
                    <SheetFooter className="border-t px-6 py-3 sm:flex-row sm:items-center sm:justify-end">
                        {!isEditing && (
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={createAnother}
                                    onCheckedChange={(checked) =>
                                        setCreateAnother(checked === true)
                                    }
                                />
                                Create another
                            </label>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            onClick={close}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            form="product-form"
                            disabled={processing}
                        >
                            {processing ? 'Saving...' : 'Save Product'}
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
            <ConfirmationDialog
                open={confirming}
                onOpenChange={setConfirming}
                title={isEditing ? 'Update product' : 'Create product'}
                description="Confirm this product change before it is saved."
                confirmLabel="Save"
                processing={processing}
                showNote={false}
                onConfirm={persist}
            />
        </>
    );
}

function TanStackTextField({
    field,
    label,
    serverError,
    children,
    className,
    required = false,
}: {
    field: {
        name: string;
        state: { meta: { errors: unknown[] } };
    };
    label: string;
    serverError?: string;
    children: ReactNode;
    className?: string;
    required?: boolean;
}) {
    const errors = fieldErrors(field.state.meta.errors, serverError);
    const isInvalid = errors.length > 0;

    return (
        <Field data-invalid={isInvalid} className={className}>
            <FieldLabel htmlFor={field.name} required={required}>
                {label}
            </FieldLabel>
            {isValidElement(children)
                ? cloneElement(children, {
                      'aria-invalid': isInvalid,
                  } as React.HTMLAttributes<HTMLElement>)
                : children}
            <FieldError errors={errors} />
        </Field>
    );
}

function ReferenceSelect({
    field,
    label,
    records,
    serverError,
}: {
    field: {
        name: string;
        state: {
            value?: string | null;
            meta: { errors: unknown[] };
        };
        handleChange: (value: string | null) => void;
        handleBlur: () => void;
    };
    label: string;
    records: Array<{ id: string; name: string }>;
    serverError?: string;
}) {
    const errors = fieldErrors(field.state.meta.errors, serverError);
    const isInvalid = errors.length > 0;

    return (
        <Field data-invalid={isInvalid}>
            <FieldLabel>{label}</FieldLabel>
            <Select
                value={field.state.value ?? 'none'}
                onValueChange={(next) => {
                    field.handleChange(next === 'none' ? null : next);
                    field.handleBlur();
                }}
            >
                <SelectTrigger className="w-full" aria-invalid={isInvalid}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">None</SelectItem>
                    {records.map((record) => (
                        <SelectItem key={record.id} value={record.id}>
                            {record.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FieldError errors={errors} />
        </Field>
    );
}
