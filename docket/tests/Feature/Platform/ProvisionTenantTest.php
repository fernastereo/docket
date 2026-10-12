<?php

declare(strict_types=1);

use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Enums\TenantStatus;
use App\Platform\Exceptions\TenantProvisioningFailed;
use App\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Events\DatabaseCreated;

// Estos tests crean bases físicas: CREATE DATABASE no corre dentro de una
// transacción, por eso DatabaseMigrations y no RefreshDatabase.
uses(DatabaseMigrations::class);

beforeEach(fn () => config(['platform.tenant_base_domain' => 'docket.test']));

// Borra las bases físicas que crearon los tests (el evento TenantDeleted las
// elimina). Solo toca tenants que existen en docket_test, nunca otras bases.
afterEach(fn () => Tenant::query()->get()->each->delete());

function databaseExists(string $name): bool
{
    return DB::selectOne('select 1 as found from pg_database where datname = ?', [$name]) !== null;
}

test('provisions a tenant with its domain, physical database and migrations', function () {
    $tenant = app(ProvisionTenant::class)->handle(
        new ProvisionTenantData(name: 'Curaduría Urbana 1 de Prueba', subdomain: 'cu1prueba'),
    );

    $tenant->refresh();

    expect($tenant->status)->toBe(TenantStatus::Demo)
        ->and($tenant->provisioned_at)->not->toBeNull()
        ->and($tenant->failure_reason)->toBeNull()
        ->and($tenant->domains()->pluck('domain')->all())->toBe(['cu1prueba.docket.test'])
        ->and(databaseExists($tenant->database()->getName()))->toBeTrue()
        ->and($tenant->database()->getName())->toBe('tenanttest_'.$tenant->id);

    // Las migraciones corrieron dentro de la base del tenant.
    $tenant->run(fn () => expect(DB::getSchemaBuilder()->hasTable('migrations'))->toBeTrue());
});

test('can provision directly as active', function () {
    $tenant = app(ProvisionTenant::class)->handle(
        new ProvisionTenantData(name: 'Curaduría Activa', subdomain: 'cuactiva', status: TenantStatus::Active),
    );

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
});

test('rejects invalid data without leaving any trace', function (array $override) {
    $data = new ProvisionTenantData(...array_merge(['name' => 'Curaduría Válida', 'subdomain' => 'cuvalida'], $override));

    expect(fn () => app(ProvisionTenant::class)->handle($data))->toThrow(ValidationException::class)
        ->and(Tenant::count())->toBe(0);
})->with([
    'reserved subdomain' => [['subdomain' => 'admin']],
    'malformed subdomain' => [['subdomain' => 'Cu_Mala']],
    'name too short' => [['name' => 'ab']],
    'lifecycle status not allowed at birth' => [['status' => TenantStatus::Terminated]],
]);

test('rejects a subdomain already taken', function () {
    app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Primera', subdomain: 'cuunica'));

    expect(fn () => app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Segunda', subdomain: 'cuunica')))
        ->toThrow(ValidationException::class)
        ->and(Tenant::count())->toBe(1);
});

test('a failure after the database was created compensates: no database, no domain, tenant marked failed', function () {
    $createdDatabase = null;

    Event::listen(DatabaseCreated::class, function (DatabaseCreated $event) use (&$createdDatabase) {
        $createdDatabase = $event->tenant->database()->getName();

        throw new RuntimeException('boom');
    });

    $data = new ProvisionTenantData(name: 'Curaduría Que Falla', subdomain: 'cufalla');

    expect(fn () => app(ProvisionTenant::class)->handle($data))->toThrow(TenantProvisioningFailed::class);

    $tenant = Tenant::sole();

    expect($createdDatabase)->not->toBeNull()
        ->and(databaseExists($createdDatabase))->toBeFalse()
        ->and($tenant->status)->toBe(TenantStatus::Failed)
        ->and($tenant->failure_reason)->toBe('boom')
        ->and($tenant->provisioned_at)->toBeNull()
        ->and($tenant->domains()->count())->toBe(0);
});

test('a failed tenant can be deleted even though its database is already gone', function () {
    Event::listen(DatabaseCreated::class, fn () => throw new RuntimeException('boom'));

    expect(fn () => app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Falla', subdomain: 'cuborrar')))
        ->toThrow(TenantProvisioningFailed::class);

    Tenant::sole()->delete();

    expect(Tenant::count())->toBe(0);
});

test('after a failed provisioning the subdomain can be used again', function () {
    $listener = fn () => throw new RuntimeException('boom');
    Event::listen(DatabaseCreated::class, $listener);

    expect(fn () => app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Falla', subdomain: 'cureintento')))
        ->toThrow(TenantProvisioningFailed::class);

    Event::forget(DatabaseCreated::class);

    $tenant = app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Reintento', subdomain: 'cureintento'));

    expect($tenant->refresh()->status)->toBe(TenantStatus::Demo)
        ->and(Tenant::count())->toBe(2)
        ->and(Tenant::where('status', TenantStatus::Failed)->count())->toBe(1);
});
