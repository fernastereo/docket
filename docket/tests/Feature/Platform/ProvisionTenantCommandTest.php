<?php

declare(strict_types=1);

use App\Platform\Enums\TenantStatus;
use App\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;

// DatabaseMigrations (no RefreshDatabase): el caso exitoso crea una base
// física, que no corre dentro de la transacción de RefreshDatabase.
uses(DatabaseMigrations::class);

beforeEach(fn () => config(['platform.tenant_base_domain' => 'docket.test']));

test('reports validation errors and creates nothing', function () {
    $this->artisan('tenants:provision', ['name' => 'Curaduría Válida', 'subdomain' => 'admin'])
        ->expectsOutputToContain('reservado')
        ->assertFailed();

    expect(Tenant::count())->toBe(0);
});

test('a malformed subdomain fails with a clear message', function () {
    $this->artisan('tenants:provision', ['name' => 'Curaduría Válida', 'subdomain' => 'cu_mala'])
        ->expectsOutputToContain('solo admite minúsculas')
        ->assertFailed();
});

test('provisions a tenant, lowercasing the subdomain the user typed', function () {
    $this->artisan('tenants:provision', ['name' => 'Curaduría Por Comando', 'subdomain' => 'CuComando', '--active' => true])
        ->expectsOutputToContain('cucomando.docket.test')
        ->assertSuccessful();

    $tenant = Tenant::sole();

    expect($tenant->status)->toBe(TenantStatus::Active)
        ->and($tenant->domains()->pluck('domain')->all())->toBe(['cucomando.docket.test']);

    $tenant->delete(); // elimina también la base física
});
