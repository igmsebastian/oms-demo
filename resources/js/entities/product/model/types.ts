export type ProductTaxonomy = {
    id: string;
    name: string;
    slug: string;
    description?: string | null;
    abbreviation?: string | null;
    code?: string | null;
    hex_code?: string | null;
    color?: string | null;
    is_active: boolean;
};

export type Product = {
    id: string;
    sku: string;
    name: string;
    description?: string | null;
    category?: ProductTaxonomy | null;
    brand?: ProductTaxonomy | null;
    unit?: ProductTaxonomy | null;
    size?: ProductTaxonomy | null;
    color?: ProductTaxonomy | null;
    tags?: ProductTaxonomy[];
    product_category_id?: string | null;
    product_brand_id?: string | null;
    product_unit_id?: string | null;
    product_size_id?: string | null;
    product_color_id?: string | null;
    tag_ids?: string[];
    image_url?: string | null;
    price: string | number;
    stock_quantity: number;
    low_stock_threshold: number;
    is_low_stock: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type ProductReferenceLists = {
    categories: ProductTaxonomy[];
    brands: ProductTaxonomy[];
    units: ProductTaxonomy[];
    sizes: ProductTaxonomy[];
    colors: ProductTaxonomy[];
    tags: ProductTaxonomy[];
};

export type ProductMetrics = {
    total_products: number;
    in_stock_products: number;
    low_stock_products: number;
    no_stock_products: number;
};
