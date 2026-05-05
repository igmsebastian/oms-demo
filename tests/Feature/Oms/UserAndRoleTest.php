<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('user model uses ulids enum roles defaults name mapping and hidden sensitive fields', function () {
    $user = User::create([
        'name' => 'Mara Santos Reyes',
        'email' => 'mara.reyes@mydemo.com',
        'password' => 'password',
    ]);

    expect(Str::isUlid($user->id))->toBeTrue()
        ->and($user->fresh()->role)->toBe(UserRole::User)
        ->and($user->fresh()->name)->toBe('Mara Santos Reyes')
        ->and($user->fresh()->first_name)->toBe('Mara')
        ->and($user->fresh()->middle_name)->toBe('Santos')
        ->and($user->fresh()->last_name)->toBe('Reyes')
        ->and($user->fresh()->toArray())->not->toHaveKeys([
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
});

test('role helpers and gates separate admins from customers', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewReports'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewReports'))->toBeFalse();
});

test('dashboard and admin pages enforce expected role access', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/index')
            ->where('auth.user.is_admin', false)
            ->has('dashboard.kpis')
        );

    $this->actingAs($admin)->get(route('products.index'))->assertOk();
    $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    $this->actingAs($admin)->get(route('product-management.index'))->assertOk();

    $this->actingAs($user)->get(route('products.index'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('product-management.index'))->assertForbidden();
});

test('sidebar source keeps customer and admin navigation separated', function () {
    $sidebar = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    expect($sidebar)->toContain("title: isAdmin ? 'Orders' : 'My Orders'")
        ->and($sidebar)->toContain("title: 'Inventory'")
        ->and($sidebar)->toContain("title: 'Reports'")
        ->and($sidebar)->toContain("title: 'Product Management'");
});
