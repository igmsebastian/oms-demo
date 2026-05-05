<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn (): array => ProductTaxonomyResource::make($this->category)->resolve($request)),
            'brand' => $this->whenLoaded('brand', fn (): array => ProductTaxonomyResource::make($this->brand)->resolve($request)),
            'unit' => $this->whenLoaded('unit', fn (): array => ProductTaxonomyResource::make($this->unit)->resolve($request)),
            'size' => $this->whenLoaded('size', fn (): array => ProductTaxonomyResource::make($this->size)->resolve($request)),
            'color' => $this->whenLoaded('color', fn (): array => ProductTaxonomyResource::make($this->color)->resolve($request)),
            'tags' => $this->whenLoaded('tags', fn (): array => ProductTaxonomyResource::collection($this->tags)->resolve($request), []),
            'product_category_id' => $this->product_category_id,
            'product_brand_id' => $this->product_brand_id,
            'product_unit_id' => $this->product_unit_id,
            'product_size_id' => $this->product_size_id,
            'product_color_id' => $this->product_color_id,
            'tag_ids' => $this->whenLoaded('tags', fn (): array => $this->tags->pluck('id')->values()->all(), []),
            'image_url' => null,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->isLowStock(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
