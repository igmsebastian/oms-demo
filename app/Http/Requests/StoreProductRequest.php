<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', Rule::unique(Product::class, 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'ulid', 'exists:product_categories,id'],
            'product_brand_id' => ['nullable', 'ulid', 'exists:product_brands,id'],
            'product_unit_id' => ['nullable', 'ulid', 'exists:product_units,id'],
            'product_size_id' => ['nullable', 'ulid', 'exists:product_sizes,id'],
            'product_color_id' => ['nullable', 'ulid', 'exists:product_colors,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['ulid', 'exists:product_tags,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
