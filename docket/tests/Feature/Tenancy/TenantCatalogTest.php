<?php

declare(strict_types=1);

use App\Platform\Enums\TenantStatus;
use App\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;

uses(RefreshDatabase::class);

// Crear el Tenant dispara TenantCreated, que activa el pipeline de stancl
// (CreateDatabase + MigrateDatabase) y necesita Postgres real. Aquí solo se
// prueba el catálogo, así que se apaga ese único evento. No usar
// Tenant::withoutEvents(): también apagaría `creating`, donde stancl genera el id.
beforeEach(fn () => Event::fake([TenantCreated::class]));

function makeCatalogTenant(array $attributes = []): Tenant
{
    return Tenant::create(array_merge([
        'name' => 'Curaduría Urbana 1 de Prueba',
    ], $attributes));
}

test('name and status are real columns, not buried in the data json', function () {
    $tenant = makeCatalogTenant(['status' => TenantStatus::Demo]);

    $row = DB::table('tenants')->where('id', $tenant->id)->first();

    expect($row->name)->toBe('Curaduría Urbana 1 de Prueba')
        ->and($row->status)->toBe('demo')
        ->and($row->data)->not->toContain('name')
        ->and($row->data)->not->toContain('status');
});

test('a new tenant starts in provisioning', function () {
    $tenant = makeCatalogTenant()->refresh();

    expect($tenant->status)->toBe(TenantStatus::Provisioning)
        ->and($tenant->provisioned_at)->toBeNull()
        ->and($tenant->failure_reason)->toBeNull();
});

test('status is cast to the TenantStatus enum', function () {
    $tenant = makeCatalogTenant(['status' => 'active'])->refresh();

    expect($tenant->status)->toBe(TenantStatus::Active);
});

test('tenant ids are uuids', function () {
    expect(makeCatalogTenant()->id)->toBeUuid();
});

test('only demo, active and suspended tenants are accessible', function (TenantStatus $status, bool $accessible) {
    expect($status->isAccessible())->toBe($accessible);
})->with([
    [TenantStatus::Provisioning, false],
    [TenantStatus::Failed, false],
    [TenantStatus::Demo, true],
    [TenantStatus::Active, true],
    [TenantStatus::Suspended, true],
    [TenantStatus::Terminated, false],
]);

test('suspended is read-only, never a total lockout (ADR-010)', function (TenantStatus $status, bool $writable) {
    expect($status->isWritable())->toBe($writable);
})->with([
    [TenantStatus::Demo, true],
    [TenantStatus::Active, true],
    [TenantStatus::Suspended, false],
    [TenantStatus::Provisioning, false],
    [TenantStatus::Failed, false],
    [TenantStatus::Terminated, false],
]);
