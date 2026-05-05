<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique(Product::class, 'sku')->ignore($product)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'ulid', 'exists:product_categories,id'],
            'product_brand_id' => ['nullable', 'ulid', 'exists:product_brands,id'],
            'product_unit_id' => ['nullable', 'ulid', 'exists:product_units,id'],
            'product_size_id' => ['nullable', 'ulid', 'exists:product_sizes,id'],
            'product_color_id' => ['nullable', 'ulid', 'exists:product_colors,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['ulid', 'exists:product_tags,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
