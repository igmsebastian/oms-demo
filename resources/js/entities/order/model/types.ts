import type { Product } from '@/entities/product/model/types';
import type { UserSummary } from '@/entities/user/model/types';

export type OrderStatusPayload = {
    id: number;
    name: string;
    label: string;
};

export type OrderRefund = {
    id: string;
    status: {
        name: string;
        label: string;
    };
    amount: string | number;
    reason?: string | null;
    metadata?: Record<string, unknown> | null;
    stock_disposition?: string | null;
    processed_at?: string | null;
    created_at: string;
};

export type OrderItem = {
    id: string;
    order_id: string;
    product_id: string;
    product_name: string;
    product_sku: string;
    quantity: number;
    cancelled_quantity: number;
    refunded_quantity: number;
    unit_price: string | number;
    line_total: string | number;
    product?: Product | null;
    created_at: string;
    updated_at: string;
};

export type OrderActivity = {
    id: string;
    order_id: string;
    actor_id?: string | null;
    actor_role?: number | null;
    event: string;
    title: string;
    description?: string | null;
    from_status?: string | null;
    to_status?: string | null;
    metadata?: Record<string, unknown> | null;
    actor?: UserSummary | null;
    created_at: string;
};

export type Order = {
    id: string;
    user_id: string;
    user_address_id?: string | null;
    order_number: string;
    status: OrderStatusPayload;
    total_amount: string | number;
    shipping_address_line_1: string;
    shipping_address_line_2?: string | null;
    shipping_city: string;
    shipping_country: string;
    shipping_post_code: string;
    shipping_full_address: string;
    cancellation_reason?: string | null;
    confirmed_at?: string | null;
    cancelled_at?: string | null;
    refunded_at?: string | null;
    customer?: UserSummary | null;
    user?: UserSummary | null;
    items?: OrderItem[];
    activities?: OrderActivity[];
    refunds?: OrderRefund[];
    allowed_actions: string[];
    available_statuses: OrderStatusPayload[];
    created_at: string;
    updated_at: string;
};
