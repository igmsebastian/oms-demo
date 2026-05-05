<?php

namespace App\Filters;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    public function name(string $value): Builder
    {
        return $this->builder
            ->where('first_name', 'like', "%{$value}%")
            ->orWhere('middle_name', 'like', "%{$value}%")
            ->orWhere('last_name', 'like', "%{$value}%");
    }

    public function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', "%{$value}%");
    }

    public function role(string|int $value): Builder
    {
        $role = is_numeric($value)
            ? UserRole::tryFrom((int) $value)
            : UserRole::tryFrom(strtolower((string) $value) === 'admin' ? UserRole::Admin->value : UserRole::User->value);

        return $role
            ? $this->builder->where('role', $role->value)
            : $this->builder;
    }
}
