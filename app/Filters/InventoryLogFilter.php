<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class InventoryLogFilter extends QueryFilter
{
    public function product_id(string $value): Builder
    {
        return $this->builder->where('product_id', $value);
    }

    public function order_id(string $value): Builder
    {
        return $this->builder->where('order_id', $value);
    }

    public function change_type(string $value): Builder
    {
        return $this->builder->where('change_type', $value);
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
