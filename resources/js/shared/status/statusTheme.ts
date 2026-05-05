export type StatusTone =
    | 'neutral'
    | 'success'
    | 'warning'
    | 'destructive'
    | 'info';

export const statusBlue = '#2563eb';

const orderStatusColors: Record<string, string> = {
    cancelled: '#dc2626',
    cancellation_requested: '#d97706',
    completed: '#16a34a',
    confirmed: statusBlue,
    delivered: '#059669',
    new: '#64748b',
    packed: '#4f46e5',
    partially_cancelled: '#ea580c',
    pending: '#f59e0b',
    processing: '#0891b2',
    refund_pending: '#7c3aed',
    refunded: '#0d9488',
    shipped: '#0284c7',
};

const inventoryStatusColors: Record<string, string> = {
    active: '#16a34a',
    inactive: '#64748b',
    in_stock: '#16a34a',
    low_stock: '#f59e0b',
    no_stock: '#dc2626',
    out_of_stock: '#dc2626',
    total: statusBlue,
};

const statusTones: Record<string, StatusTone> = {
    active: 'success',
    cancelled: 'destructive',
    cancellation_requested: 'warning',
    completed: 'success',
    confirmed: 'info',
    delivered: 'success',
    in_stock: 'success',
    inactive: 'neutral',
    low_stock: 'warning',
    new: 'neutral',
    no_stock: 'destructive',
    packed: 'info',
    partially_cancelled: 'warning',
    pending: 'warning',
    processing: 'info',
    refund_pending: 'warning',
    refunded: 'success',
    shipped: 'info',
};

export function orderStatusColor(status: string): string {
    return orderStatusColors[statusKey(status)] ?? statusBlue;
}

export function inventoryStatusColor(status: string): string {
    return inventoryStatusColors[statusKey(status)] ?? statusBlue;
}

export function statusColor(status: string): string {
    const key = statusKey(status);

    return orderStatusColors[key] ?? inventoryStatusColors[key] ?? statusBlue;
}

export function statusTone(status: string): StatusTone {
    return statusTones[statusKey(status)] ?? 'neutral';
}

export function statusKey(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}
