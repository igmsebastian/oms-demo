<?php

namespace App\Services;

use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductTaxonomyService
{
    public const LEGACY_REFERENCE_CACHE_KEY = 'products.taxonomy.references';

    public const REFERENCE_CACHE_KEY = 'products.taxonomy.references.v2';

    public const LIST_PREFIX = 'products.taxonomy.list';

    public function __construct(
        protected OmsCacheService $cache,
    ) {}

    /**
     * @return array<string, class-string<Model>>
     */
    public function modules(): array
    {
        return [
            'categories' => ProductCategory::class,
            'brands' => ProductBrand::class,
            'units' => ProductUnit::class,
            'sizes' => ProductSize::class,
            'colors' => ProductColor::class,
            'tags' => ProductTag::class,
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function references(): array
    {
        return Cache::remember(self::REFERENCE_CACHE_KEY, now()->addMinutes(30), function (): array {
            return collect($this->modules())
                ->mapWithKeys(fn (string $model, string $module): array => [
                    $module => $model::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Model $record): array => $this->referencePayload($record))
                        ->values()
                        ->all(),
                ])
                ->all();
        });
    }

    public function list(string $module, ?string $keyword = null): Collection
    {
        $model = $this->modelFor($module);

        return $model::query()
            ->when($keyword, fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPayload(string $module, ?string $keyword = null): array
    {
        return $this->cache->remember(
            OmsCacheService::TAXONOMY_VERSION_KEY,
            self::LIST_PREFIX,
            [
                'module' => $module,
                'keyword' => $keyword,
            ],
            now()->addMinutes(30),
            fn (): array => $this->list($module, $keyword)
                ->map(fn (Model $record): array => $this->referencePayload($record))
                ->values()
                ->all(),
        );
    }

    public function create(string $module, array $data): Model
    {
        $model = $this->modelFor($module);
        $record = $model::query()->create($this->payload($data));
        $this->invalidate();

        return $record;
    }

    public function update(string $module, string $id, array $data): Model
    {
        $record = $this->find($module, $id);
        $record->update($this->payload($data, false));
        $this->invalidate();

        return $record->refresh();
    }

    public function delete(string $module, string $id): void
    {
        $this->find($module, $id)->delete();
        $this->invalidate();
    }

    public function find(string $module, string $id): Model
    {
        $model = $this->modelFor($module);

        return $model::query()->findOrFail($id);
    }

    /**
     * @return class-string<Model>
     */
    public function modelFor(string $module): string
    {
        $model = $this->modules()[$module] ?? null;

        if (! $model) {
            throw new InvalidArgumentException('Choose a valid product management module.');
        }

        return $model;
    }

    public function invalidate(): void
    {
        Cache::forget(self::REFERENCE_CACHE_KEY);
        Cache::forget(self::LEGACY_REFERENCE_CACHE_KEY);
        $this->cache->invalidateTaxonomy();
        $this->cache->invalidateProducts();
    }

    /**
     * @return array<string, mixed>
     */
    protected function referencePayload(Model $record): array
    {
        return [
            'id' => $record->getKey(),
            'name' => $record->getAttribute('name'),
            'slug' => $record->getAttribute('slug'),
            'description' => $record->getAttribute('description'),
            'abbreviation' => $record->getAttribute('abbreviation'),
            'code' => $record->getAttribute('code'),
            'hex_code' => $record->getAttribute('hex_code'),
            'color' => $record->getAttribute('color'),
            'is_active' => (bool) $record->getAttribute('is_active'),
            'created_at' => $this->serializeDate($record->getAttribute('created_at')),
            'updated_at' => $this->serializeDate($record->getAttribute('updated_at')),
        ];
    }

    protected function serializeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $value ? (string) $value : null;
    }

    protected function payload(array $data, bool $creating = true): array
    {
        if ($creating || array_key_exists('name', $data)) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        }

        return $data;
    }
}
