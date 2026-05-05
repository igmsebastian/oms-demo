<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrderActivityFilter extends QueryFilter
{
    public function order_id(string $value): Builder
    {
        return $this->builder->where('order_id', $value);
    }

    public function actor_id(string $value): Builder
    {
        return $this->builder->where('actor_id', $value);
    }

    public function event(string $value): Builder
    {
        return $this->builder->where('event', $value);
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
