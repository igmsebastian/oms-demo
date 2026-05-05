<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'orders' => $this->resource['orders'] ?? [],
            'revenue' => $this->resource['revenue'] ?? [],
            'inventory' => $this->resource['inventory'] ?? [],
            'low_stock_count' => $this->resource['low_stock_count'] ?? 0,
            'series' => $this->resource['series'] ?? [],
            'date_from' => $this->resource['date_from'] ?? null,
            'date_to' => $this->resource['date_to'] ?? null,
        ];
    }
}
