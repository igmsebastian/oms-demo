<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends QueryFilter
{
    public function keyword(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('sku', 'like', "%{$value}%")
                ->orWhere('description', 'like', "%{$value}%");
        });
    }

    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function sku(string $value): Builder
    {
        return $this->builder->where('sku', 'like', "%{$value}%");
    }

    public function is_active(mixed $value): Builder
    {
        return $this->builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function low_stock(mixed $value): Builder
    {
        if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return $this->builder;
        }

        return $this->builder->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function stock_status(string $value): Builder
    {
        return match ($value) {
            'in_stock' => $this->builder->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '>', 'low_stock_threshold'),
            'low_stock' => $this->builder->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
            'no_stock' => $this->builder->where('stock_quantity', 0),
            default => $this->builder,
        };
    }

    public function category_id(string $value): Builder
    {
        return $this->builder->where('product_category_id', $value);
    }

    public function brand_id(string $value): Builder
    {
        return $this->builder->where('product_brand_id', $value);
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function tag_ids(array|string $value): Builder
    {
        $tagIds = is_array($value) ? $value : [$value];

        return $this->builder->whereHas('tags', fn (Builder $query) => $query->whereIn('product_tags.id', $tagIds));
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '<=', $value);
    }
}
