<?php

namespace App\Http\Resources;

use App\Models\OrderActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderActivity
 */
class OrderActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'actor_id' => $this->actor_id,
            'actor_role' => $this->actor_role?->value,
            'event' => $this->event->value,
            'title' => $this->title,
            'description' => $this->description,
            'from_status' => $this->from_status?->nameValue(),
            'to_status' => $this->to_status?->nameValue(),
            'metadata' => $this->metadata,
            'actor' => $this->whenLoaded('actor'),
            'created_at' => $this->created_at,
        ];
    }
}
