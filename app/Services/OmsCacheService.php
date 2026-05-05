<?php

namespace App\Services;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JsonException;

class OmsCacheService
{
    public const REPORTS_VERSION_KEY = 'oms.cache.reports.version';

    public const ORDERS_VERSION_KEY = 'oms.cache.orders.version';

    public const PRODUCTS_VERSION_KEY = 'oms.cache.products.version';

    public const TAXONOMY_VERSION_KEY = 'oms.cache.taxonomy.version';

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $versionKey, string $prefix, array $parts, DateTimeInterface $ttl, Closure $callback): mixed
    {
        return Cache::remember($this->key($versionKey, $prefix, $parts), $ttl, $callback);
    }

    public function key(string $versionKey, string $prefix, array $parts = []): string
    {
        return $prefix.':v'.$this->version($versionKey).':'.$this->fingerprint($parts);
    }

    public function version(string $versionKey): int
    {
        return (int) Cache::rememberForever($versionKey, fn (): int => 1);
    }

    public function invalidateReports(): void
    {
        $this->bump(self::REPORTS_VERSION_KEY);
    }

    public function invalidateOrders(): void
    {
        $this->bump(self::ORDERS_VERSION_KEY);
    }

    public function invalidateProducts(): void
    {
        $this->bump(self::PRODUCTS_VERSION_KEY);
    }

    public function invalidateTaxonomy(): void
    {
        $this->bump(self::TAXONOMY_VERSION_KEY);
    }

    /**
     * @return array{
     *     ids: array<int, string>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     path: string,
     *     query: array<string, mixed>
     * }
     */
    public function paginatorPayload(LengthAwarePaginatorContract $paginator): array
    {
        return [
            'ids' => collect($paginator->items())
                ->map(fn (Model $model): string => (string) $model->getKey())
                ->values()
                ->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'path' => $paginator->path(),
            'query' => request()->query(),
        ];
    }

    /**
     * @param  array{
     *     ids?: array<int, string>,
     *     total?: int,
     *     per_page?: int,
     *     current_page?: int,
     *     path?: string,
     *     query?: array<string, mixed>
     * }  $payload
     */
    public function restorePaginator(array $payload, EloquentCollection $models): LengthAwarePaginator
    {
        $modelsById = $models->keyBy(fn (Model $model): string => (string) $model->getKey());
        $orderedModels = collect($payload['ids'] ?? [])
            ->map(fn (string $id): ?Model => $modelsById->get($id))
            ->filter()
            ->values();

        return new LengthAwarePaginator(
            $orderedModels,
            (int) ($payload['total'] ?? $orderedModels->count()),
            (int) ($payload['per_page'] ?? 15),
            (int) ($payload['current_page'] ?? 1),
            [
                'path' => $payload['path'] ?? LengthAwarePaginator::resolveCurrentPath(),
                'query' => $payload['query'] ?? request()->query(),
            ],
        );
    }

    protected function bump(string $versionKey): void
    {
        if (! Cache::has($versionKey)) {
            Cache::forever($versionKey, 1);
        }

        Cache::increment($versionKey);
    }

    protected function fingerprint(array $parts): string
    {
        try {
            return sha1(json_encode($this->normalize($parts), JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return sha1(serialize($parts));
        }
    }

    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Collection) {
            return $this->normalize($value->all());
        }

        if (! is_array($value)) {
            return $value;
        }

        ksort($value);

        return collect($value)
            ->map(fn (mixed $item): mixed => $this->normalize($item))
            ->all();
    }
}
