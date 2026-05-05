<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_address_id' => $this->user_address_id,
            'order_number' => $this->order_number,
            'status' => [
                'id' => $this->status?->value,
                'name' => $this->status?->nameValue(),
                'label' => $this->status?->label(),
            ],
            'total_amount' => $this->total_amount,
            'shipping_address_line_1' => $this->shipping_address_line_1,
            'shipping_address_line_2' => $this->shipping_address_line_2,
            'shipping_city' => $this->shipping_city,
            'shipping_country' => $this->shipping_country,
            'shipping_post_code' => $this->shipping_post_code,
            'shipping_full_address' => $this->shipping_full_address,
            'cancellation_reason' => $this->cancellation_reason,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'refunded_at' => $this->refunded_at,
            'user' => $this->whenLoaded('user'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'activities' => OrderActivityResource::collection($this->whenLoaded('activities')),
            'refunds' => $this->whenLoaded('refunds'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
