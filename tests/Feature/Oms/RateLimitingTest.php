<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

function routeMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)->gatherMiddleware();
}

function routeHasThrottle(string $routeName, string $limiter): bool
{
    return collect(routeMiddleware($routeName))
        ->contains(fn (string $middleware): bool => str_contains($middleware, "ThrottleRequests:{$limiter}") || $middleware === "throttle:{$limiter}");
}

test('named rate limiters are applied to api web write export and sensitive routes', function () {
    expect(routeHasThrottle('api.products.index', 'api'))->toBeTrue()
        ->and(routeHasThrottle('api.products.store', 'api'))->toBeTrue()
        ->and(routeHasThrottle('api.products.store', 'write-actions'))->toBeTrue()
        ->and(routeHasThrottle('api.orders.remarks.store', 'order-remarks'))->toBeTrue()
        ->and(routeHasThrottle('products.store', 'write-actions'))->toBeTrue()
        ->and(routeHasThrottle('orders.confirm', 'write-actions'))->toBeTrue()
        ->and(routeHasThrottle('reports.export', 'report-exports'))->toBeTrue()
        ->and(routeHasThrottle('user-password.update', 'sensitive-actions'))->toBeTrue();
});

test('api write actions are rate limited per authenticated user and route', function () {
    $admin = User::factory()->admin()->create();

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($admin)
            ->postJson('/api/products', [])
            ->assertUnprocessable();
    }

    $this->actingAs($admin)
        ->postJson('/api/products', [])
        ->assertTooManyRequests();
});
