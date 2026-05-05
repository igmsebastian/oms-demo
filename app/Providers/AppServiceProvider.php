<?php

namespace App\Providers;

use App\Contracts\Repositories\InventoryLogRepositoryInterface;
use App\Contracts\Repositories\OrderActivityRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Order;
use App\Models\Product;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReportPolicy;
use App\Repositories\InventoryLogRepository;
use App\Repositories\OrderActivityRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(InventoryLogRepositoryInterface::class, InventoryLogRepository::class);
        $this->app->bind(OrderActivityRepositoryInterface::class, OrderActivityRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::define('viewReports', [ReportPolicy::class, 'view']);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($this->rateLimitKey($request, 'api')));

        RateLimiter::for('write-actions', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($this->rateLimitKey($request, 'write:'.$this->routeKey($request))));

        RateLimiter::for('order-remarks', fn (Request $request): Limit => Limit::perMinute(10)
            ->by($this->rateLimitKey($request, 'remarks:'.$this->routeParameterKey($request, 'order'))));

        RateLimiter::for('report-exports', fn (Request $request): Limit => Limit::perMinute(10)
            ->by($this->rateLimitKey($request, 'exports:'.$this->routeKey($request))));

        RateLimiter::for('sensitive-actions', fn (Request $request): Limit => Limit::perMinute(6)
            ->by($this->rateLimitKey($request, 'sensitive:'.$this->routeKey($request))));
    }

    protected function rateLimitKey(Request $request, string $scope): string
    {
        $identifier = $request->user()?->getAuthIdentifier()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();

        return $scope.'|'.$identifier;
    }

    protected function routeKey(Request $request): string
    {
        return $request->route()?->getName() ?? $request->path();
    }

    protected function routeParameterKey(Request $request, string $parameter): string
    {
        $value = $request->route($parameter);

        if ($value instanceof UrlRoutable) {
            return (string) $value->getRouteKey();
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $this->routeKey($request);
    }
}
