<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'quantity' => $this->quantity,
            'cancelled_quantity' => $this->cancelled_quantity,
            'refunded_quantity' => $this->refunded_quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'product' => $this->whenLoaded('product', fn (): array => ProductResource::make($this->product)->resolve($request)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
