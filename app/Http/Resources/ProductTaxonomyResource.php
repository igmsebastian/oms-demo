<?php

namespace App\Http\Resources;

use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductBrand
 * @mixin ProductCategory
 * @mixin ProductColor
 * @mixin ProductSize
 * @mixin ProductTag
 * @mixin ProductUnit
 */
class ProductTaxonomyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?? null,
            'abbreviation' => $this->abbreviation ?? null,
            'code' => $this->code ?? null,
            'hex_code' => $this->hex_code ?? null,
            'color' => $this->color ?? null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
