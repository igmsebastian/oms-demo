<?php

namespace App\Enums;

enum RefundStockDisposition: string
{
    case GoodStock = 'good_stock';
    case BadStock = 'bad_stock';
    case PendingReview = 'pending_review';

    public const MetadataKey = 'stock_disposition';

    /**
     * @return array<int, string>
     */
    public static function completionValues(): array
    {
        return [
            self::GoodStock->value,
            self::BadStock->value,
        ];
    }
}
