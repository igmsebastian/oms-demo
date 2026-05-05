import type { CSSProperties } from 'react';
import {
    inventoryStatusColor,
    orderStatusColor,
    statusBlue,
} from '@/shared/status/statusTheme';

export const chartBlue = statusBlue;

export const chartTooltipProps = {
    contentStyle: {
        backgroundColor: 'var(--card)',
        border: '1px solid var(--border)',
        borderRadius: '0.75rem',
        boxShadow:
            '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        color: 'var(--card-foreground)',
    } satisfies CSSProperties,
    itemStyle: {
        color: 'var(--card-foreground)',
    } satisfies CSSProperties,
    labelStyle: {
        color: 'var(--card-foreground)',
        fontWeight: 600,
    } satisfies CSSProperties,
};

export function statusChartColor(status: string): string {
    return orderStatusColor(status);
}

export function inventoryChartColor(metric: string): string {
    return inventoryStatusColor(metric);
}
