<?php

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class QueryFilter
{
    protected Builder $builder;

    public function __construct(
        protected Request $request
    ) {}

    /**
     * Apply filtering of results.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $this->applyFiltersFromRequest();
        $this->applySortFromRequest();

        return $this->builder;
    }

    public function user(): Authenticatable | null
    {
        return $this->request->user();
    }

    public function filters(): array
    {
        $filters = $this->request->input('filters', []);
        return is_array($filters) ? $filters : [];
    }

    public function sortParameters(): array
    {
        $sortParameters = $this->request->input('sorts', []);
        return is_array($sortParameters) ? $sortParameters : [];
    }

    public function isSimple(): bool
    {
        return $this->request->boolean('s');
    }

    public function perPage(?int $default = null): ?int
    {
        $perPage = $this->request->query('per_page');

        if ($perPage === null || $perPage === '') {
            return $default;
        }

        if (!is_numeric($perPage)) {
            return $default;
        }

        return (int) $perPage;
    }

    public function isUnpaginated(): bool
    {
        $perPage = $this->perPage();
        return $perPage !== null && $perPage === -1;
    }

    protected function applyFiltersFromRequest(): void
    {
        foreach ($this->filters() as $filterName => $filterValue) {
            if ($this->isEmptyFilterValue($filterValue)) {
                continue;
            }

            if (method_exists($this, $filterName)) {
                $this->{$filterName}($filterValue);
            }
        }
    }

    protected function applySortFromRequest(): void
    {
        foreach ($this->sortParameters() as $columnName => $direction) {
            $this->sort([
                'by'    => $columnName,
                'order' => $direction,
            ]);
        }
    }

    public function sort(array $sortOptions  = []): Builder
    {
        $columnName = $sortOptions['by'] ?? null;
        $direction  = $sortOptions['order'] ?? 'desc';

        if ($columnName === null) {
            return $this->builder;
        }

        $tableName = $this->builder->getModel()->getTable();

        if (! Schema::hasColumn($tableName, $columnName)) {
            return $this->builder;
        }

        $direction = strtolower((string) $direction);
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return $this->builder->orderBy($columnName, $direction);
    }

    public function limit(int $value = 10): Builder
    {
        return $this->builder->limit($value);
    }

    protected function isEmptyFilterValue(mixed $value): bool
    {
        if (is_array($value)) {
            return count(array_filter($value, static fn($item) => $item !== null && $item !== '')) === 0;
        }

        return $value === null || $value === '';
    }
}
