import { Head, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Search, Trash } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type { ProductTaxonomy } from '@/entities/product/model/types';
import { CrudDialogForm } from '@/features/product-management/CrudDialogForm';
import { cn } from '@/lib/utils';
import { index as productManagementIndex } from '@/routes/product-management';
import { ActionDropdown } from '@/shared/components/ActionDropdown';
import { DataTable } from '@/shared/components/DataTable';
import { StatusBadge } from '@/shared/components/StatusBadge';

type ModulePayload = {
    name: string;
    label: string;
    records: ProductTaxonomy[];
};

type ProductManagementProps = {
    modules: Record<string, ModulePayload>;
    filters?: {
        keyword?: string | null;
    };
};

export default function ProductManagement({
    modules,
    filters = {},
}: ProductManagementProps) {
    const moduleKeys = Object.keys(modules);
    const [activeModule, setActiveModule] = useState(
        moduleKeys[0] ?? 'categories',
    );
    const module = modules[activeModule];
    const [editing, setEditing] = useState<ProductTaxonomy | null>(null);
    const [deleting, setDeleting] = useState<ProductTaxonomy | null>(null);
    const [keyword, setKeyword] = useState(filters.keyword ?? '');
    const records = module?.records ?? [];
    const activeLabel = module?.label ?? 'Categories';

    const columns = useMemo<ColumnDef<ProductTaxonomy>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Name',
                cell: ({ row }) => (
                    <span className="font-medium">{row.original.name}</span>
                ),
            },
            {
                accessorKey: 'slug',
                header: 'Slug',
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
                cell: ({ row }) => (
                    <ActionDropdown
                        items={[
                            {
                                label: 'Edit',
                                icon: Pencil,
                                onSelect: () => setEditing(row.original),
                            },
                            {
                                label: 'Delete',
                                icon: Trash,
                                destructive: true,
                                onSelect: () => setDeleting(row.original),
                            },
                        ]}
                    />
                ),
            },
        ],
        [],
    );

    return (
        <>
            <Head title="Product Management" />

            <h1 className="sr-only">Product Management</h1>

            <div className="px-4 py-6">
                <Heading
                    title="Product Management"
                    description="Manage product reference data used across inventory and orders"
                />

                <div className="flex flex-col lg:flex-row lg:space-x-12">
                    <aside className="w-full max-w-xl lg:w-48">
                        <nav
                            className="flex flex-col space-y-1 space-x-0"
                            aria-label="Product management"
                        >
                            {moduleKeys.map((key) => (
                                <Button
                                    key={key}
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    className={cn('w-full justify-start', {
                                        'bg-muted': activeModule === key,
                                    })}
                                    onClick={() => setActiveModule(key)}
                                    aria-current={
                                        activeModule === key
                                            ? 'page'
                                            : undefined
                                    }
                                >
                                    {modules[key].label}
                                </Button>
                            ))}
                        </nav>
                    </aside>

                    <Separator className="my-6 lg:hidden" />

                    <div className="min-w-0 flex-1">
                        <section className="max-w-5xl space-y-6">
                            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <Heading
                                    variant="small"
                                    title={activeLabel}
                                    description="Create and maintain values used during product setup"
                                />

                                <CrudDialogForm
                                    module={activeModule}
                                    mode="create"
                                />
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    {records.length}{' '}
                                    {records.length === 1
                                        ? 'record'
                                        : 'records'}
                                </p>

                                <form
                                    className="flex w-full gap-2 sm:max-w-sm"
                                    onSubmit={(event) => {
                                        event.preventDefault();

                                        const request =
                                            productManagementIndex.get({
                                                query: keyword
                                                    ? { keyword }
                                                    : {},
                                            });

                                        router.visit(request.url, {
                                            method: request.method,
                                            preserveScroll: true,
                                            preserveState: true,
                                        });
                                    }}
                                >
                                    <Input
                                        value={keyword}
                                        placeholder="Search"
                                        onChange={(event) =>
                                            setKeyword(event.target.value)
                                        }
                                    />

                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="icon"
                                        aria-label="Search records"
                                    >
                                        <Search className="size-4" />
                                    </Button>
                                </form>
                            </div>

                            <DataTable
                                data={records}
                                columns={columns}
                                enableColumnVisibility={false}
                                emptyTitle="No records found"
                            />
                        </section>
                    </div>
                </div>
                {editing && (
                    <CrudDialogForm
                        module={activeModule}
                        record={editing}
                        mode="edit"
                        autoOpen
                        onClose={() => setEditing(null)}
                    />
                )}
                {deleting && (
                    <CrudDialogForm
                        module={activeModule}
                        record={deleting}
                        mode="delete"
                        autoOpen
                        onClose={() => setDeleting(null)}
                    />
                )}
            </div>
        </>
    );
}

ProductManagement.layout = {
    breadcrumbs: [
        {
            title: 'Product Management',
            href: productManagementIndex(),
        },
    ],
};
