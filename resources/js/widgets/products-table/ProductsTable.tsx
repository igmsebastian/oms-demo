import { router } from '@inertiajs/react';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import { Pencil, RotateCcw, Search, Trash } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    Product,
    ProductReferenceLists,
} from '@/entities/product/model/types';
import { useProductSheetStore } from '@/features/product-form/useProductSheetStore';
import {
    destroy as destroyProduct,
    index as productsIndex,
} from '@/routes/products';
import { ActionDropdown } from '@/shared/components/ActionDropdown';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';
import { DataTable } from '@/shared/components/DataTable';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginatedResource } from '@/shared/types/pagination';

type ProductsTableProps = {
    products: PaginatedResource<Product>;
    references: ProductReferenceLists;
    filters: Record<string, string | string[]>;
    sorts: Record<string, string>;
    loading?: boolean;
    onVisit?: (next: ProductsTableVisit) => void;
};

export type ProductsTableVisit = {
    filters?: Record<string, string | string[]>;
    sorts?: Record<string, string>;
    page?: number;
    perPage?: number;
};

export function ProductsTable({
    products,
    references,
    filters,
    sorts,
    loading = false,
    onVisit,
}: ProductsTableProps) {
    const [keyword, setKeyword] = useState(filters.keyword ?? '');
    const [productToDelete, setProductToDelete] = useState<Product | null>(
        null,
    );
    const [processingDelete, setProcessingDelete] = useState(false);
    const { openEdit } = useProductSheetStore();
    const sorting = useMemo<SortingState>(
        () =>
            Object.entries(sorts ?? {}).map(([id, desc]) => ({
                id,
                desc: desc === 'desc',
            })),
        [sorts],
    );

    const visit = (next: ProductsTableVisit) => {
        if (onVisit) {
            onVisit(next);

            return;
        }

        router.get(
            productsIndex.url({
                query: {
                    filters: next.filters ?? filters,
                    sorts: next.sorts ?? sorts,
                    page: next.page ?? 1,
                    per_page: next.perPage ?? products.meta.per_page,
                },
            }),
            {},
            {
                only: ['products', 'metrics', 'filters', 'sorts'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const columns = useMemo<ColumnDef<Product>[]>(
        () => [
            {
                accessorKey: 'sku',
                header: 'SKU',
                cell: ({ row }) => (
                    <span className="font-medium">{row.original.sku}</span>
                ),
            },
            {
                accessorKey: 'name',
                header: 'Product',
                cell: ({ row }) => (
                    <div>
                        <p className="font-medium">{row.original.name}</p>
                        <p className="text-xs text-muted-foreground">
                            {row.original.category?.name ?? 'Uncategorized'}
                        </p>
                    </div>
                ),
            },
            {
                accessorKey: 'price',
                header: 'Price',
                cell: ({ row }) => <MoneyDisplay value={row.original.price} />,
            },
            {
                accessorKey: 'stock_quantity',
                header: 'Stock',
                cell: ({ row }) => {
                    const product = row.original;
                    const status =
                        product.stock_quantity === 0
                            ? 'no_stock'
                            : product.is_low_stock
                              ? 'low_stock'
                              : 'in_stock';

                    return (
                        <StatusBadge
                            status={status}
                            label={
                                status === 'no_stock'
                                    ? 'No Stock'
                                    : `${product.stock_quantity} ${status.replace('_', ' ')}`
                            }
                        />
                    );
                },
            },
            {
                accessorKey: 'is_active',
                header: 'Status',
                cell: ({ row }) => (
                    <StatusBadge
                        status={row.original.is_active ? 'active' : 'inactive'}
                        label={row.original.is_active ? 'Active' : 'Inactive'}
                    />
                ),
            },
            {
                id: 'actions',
                header: '',
                enableSorting: false,
                cell: ({ row }) => (
                    <ActionDropdown
                        items={[
                            {
                                label: 'Edit',
                                icon: Pencil,
                                onSelect: () => openEdit(row.original),
                            },
                            {
                                label: 'Delete',
                                icon: Trash,
                                destructive: true,
                                onSelect: () =>
                                    setProductToDelete(row.original),
                            },
                        ]}
                    />
                ),
            },
        ],
        [openEdit],
    );

    return (
        <div className="space-y-4">
            <DataTable
                data={products.data}
                columns={columns}
                meta={products.meta}
                sorting={sorting}
                enableRowSelection
                loading={loading}
                getRowId={(product) => product.id}
                toolbar={
                    <form
                        className="grid gap-3 @3xl/main:grid-cols-[minmax(220px,1fr)_180px_180px_160px_160px_auto_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit({ filters: { ...filters, keyword } });
                        }}
                    >
                        <Input
                            value={keyword}
                            placeholder="Search products"
                            onChange={(event) => setKeyword(event.target.value)}
                        />
                        <FilterSelect
                            label="Category"
                            value={stringFilter(filters.category_id)}
                            records={references.categories}
                            onChange={(value) =>
                                visit({
                                    filters: { ...filters, category_id: value },
                                })
                            }
                        />
                        <FilterSelect
                            label="Brand"
                            value={stringFilter(filters.brand_id)}
                            records={references.brands}
                            onChange={(value) =>
                                visit({
                                    filters: { ...filters, brand_id: value },
                                })
                            }
                        />
                        <Select
                            value={stringFilter(filters.stock_status) ?? 'all'}
                            onValueChange={(value) =>
                                visit({
                                    filters: {
                                        ...filters,
                                        stock_status:
                                            value === 'all' ? '' : value,
                                    },
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Stock" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All stock</SelectItem>
                                <SelectItem value="in_stock">
                                    In stock
                                </SelectItem>
                                <SelectItem value="low_stock">
                                    Low stock
                                </SelectItem>
                                <SelectItem value="no_stock">
                                    No stock
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <TagFilter
                            tags={references.tags}
                            selected={
                                Array.isArray(filters.tag_ids)
                                    ? filters.tag_ids
                                    : []
                            }
                            onChange={(tagIds) =>
                                visit({
                                    filters: { ...filters, tag_ids: tagIds },
                                })
                            }
                        />
                        <Button type="submit" variant="outline">
                            <Search className="size-4" />
                            Search
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                setKeyword('');
                                visit({ filters: {} });
                            }}
                        >
                            <RotateCcw className="size-4" />
                            Reset
                        </Button>
                    </form>
                }
                onSortingChange={(next) => {
                    const first = next[0];
                    visit({
                        sorts: first
                            ? { [first.id]: first.desc ? 'desc' : 'asc' }
                            : {},
                    });
                }}
                onPageChange={(page) => visit({ page })}
                onPageSizeChange={(perPage) => visit({ page: 1, perPage })}
                emptyTitle="No products found"
            />
            <ConfirmationDialog
                open={Boolean(productToDelete)}
                onOpenChange={(open) => !open && setProductToDelete(null)}
                title="Delete product"
                description={
                    productToDelete
                        ? `Delete ${productToDelete.name}.`
                        : undefined
                }
                confirmLabel="Delete"
                destructive
                processing={processingDelete}
                showNote={false}
                onConfirm={() => {
                    if (!productToDelete) {
                        return;
                    }

                    setProcessingDelete(true);
                    const request = destroyProduct.delete(productToDelete.id);
                    router.visit(request.url, {
                        method: request.method,
                        preserveScroll: true,
                        onError: () =>
                            toast.error(
                                'We could not delete the product. Please try again.',
                            ),
                        onFinish: () => {
                            setProcessingDelete(false);
                            setProductToDelete(null);
                        },
                    });
                }}
            />
        </div>
    );
}

function FilterSelect({
    label,
    value,
    records,
    onChange,
}: {
    label: string;
    value?: string;
    records: Array<{ id: string; name: string }>;
    onChange: (value: string) => void;
}) {
    return (
        <Select
            value={value ?? 'all'}
            onValueChange={(next) => onChange(next === 'all' ? '' : next)}
        >
            <SelectTrigger>
                <SelectValue placeholder={label} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">All {label.toLowerCase()}</SelectItem>
                {records.map((record) => (
                    <SelectItem key={record.id} value={record.id}>
                        {record.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function stringFilter(value: string | string[] | undefined) {
    return Array.isArray(value) ? undefined : value;
}

function TagFilter({
    tags,
    selected,
    onChange,
}: {
    tags: Array<{ id: string; name: string }>;
    selected: string[];
    onChange: (value: string[]) => void;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="justify-between"
                >
                    Tags {selected.length > 0 ? `(${selected.length})` : ''}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                {tags.map((tag) => (
                    <DropdownMenuCheckboxItem
                        key={tag.id}
                        checked={selected.includes(tag.id)}
                        onCheckedChange={(checked) =>
                            onChange(
                                checked
                                    ? [...selected, tag.id]
                                    : selected.filter((id) => id !== tag.id),
                            )
                        }
                    >
                        {tag.name}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
