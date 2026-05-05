import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type {
    Product,
    ProductMetrics,
    ProductReferenceLists,
} from '@/entities/product/model/types';
import { ProductFormSheet } from '@/features/product-form/ProductFormSheet';
import { useProductSheetStore } from '@/features/product-form/useProductSheetStore';
import { index as productsIndex } from '@/routes/products';
import { PageHeader } from '@/shared/components/PageHeader';
import type { PaginatedResource } from '@/shared/types/pagination';
import { ProductKpis } from '@/widgets/product-kpis/ProductKpis';
import { ProductsTable } from '@/widgets/products-table/ProductsTable';
import type { ProductsTableVisit } from '@/widgets/products-table/ProductsTable';

type ProductsProps = {
    products: PaginatedResource<Product>;
    metrics: ProductMetrics;
    references: ProductReferenceLists;
    filters: Record<string, string | string[]>;
    sorts: Record<string, string>;
};

export default function Products({
    products,
    metrics,
    references,
    filters,
    sorts,
}: ProductsProps) {
    const { openCreate } = useProductSheetStore();
    const [productsLoading, setProductsLoading] = useState(false);
    const visitProducts = (next: ProductsTableVisit) => {
        setProductsLoading(true);
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
                onFinish: () => setProductsLoading(false),
            },
        );
    };

    return (
        <>
            <Head title="Products / Inventory" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="px-4 lg:px-6">
                            <PageHeader
                                title="Products / Inventory"
                                description="Manage product catalog and stock levels."
                                actions={
                                    <Button type="button" onClick={openCreate}>
                                        <Plus className="size-4" />
                                        Create Product
                                    </Button>
                                }
                            />
                        </div>

                        <ProductKpis
                            metrics={metrics}
                            onFilter={(stockStatus) => {
                                visitProducts({
                                    filters: {
                                        ...filters,
                                        stock_status: stockStatus,
                                    },
                                    sorts,
                                });
                            }}
                        />

                        <div className="px-4 lg:px-6">
                            <ProductsTable
                                products={products}
                                references={references}
                                filters={filters}
                                sorts={sorts}
                                loading={productsLoading}
                                onVisit={visitProducts}
                            />
                        </div>

                        <ProductFormSheet references={references} />
                    </div>
                </div>
            </div>
        </>
    );
}

Products.layout = {
    breadcrumbs: [
        {
            title: 'Products / Inventory',
            href: productsIndex(),
        },
    ],
};
