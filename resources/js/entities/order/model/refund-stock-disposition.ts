export const refundStockDisposition = {
    goodStock: 'good_stock',
    badStock: 'bad_stock',
    pendingReview: 'pending_review',
} as const;

export type RefundStockDisposition =
    (typeof refundStockDisposition)[keyof typeof refundStockDisposition];
