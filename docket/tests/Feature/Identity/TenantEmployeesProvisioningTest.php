<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Models\Employee;
use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

// Crea bases físicas: DatabaseMigrations, no RefreshDatabase (ver ProvisionTenantTest).
uses(DatabaseMigrations::class);

beforeEach(fn () => config(['platform.tenant_base_domain' => 'docket.test']));

afterEach(fn () => Tenant::query()->get()->each->delete());

test('provisioning with a first admin creates it without a password and with the admin role', function () {
    $tenant = app(ProvisionTenant::class)->handle(new ProvisionTenantData(
        name: 'Curaduría Con Admin',
        subdomain: 'cuconadmin',
        adminName: 'Ana Pérez',
        adminEmail: 'Ana@CuConAdmin.co',
    ));

    $admin = $tenant->run(fn () => Employee::query()->with('roles')->sole());

    expect($admin->email)->toBe('ana@cuconadmin.co')
        ->and($admin->password)->toBeNull()
        ->and($admin->roles->pluck('name')->all())->toBe([EmployeeRole::Admin->value]);
});

test('provisioning without an admin still seeds the roles and creates no employee', function () {
    $tenant = app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Curaduría Sin Admin', subdomain: 'cusinadmin'));

    expect($tenant->run(fn () => Role::query()->count()))->toBe(count(EmployeeRole::cases()))
        ->and($tenant->run(fn () => Employee::query()->count()))->toBe(0);
});

test('an admin name without an email (or the reverse) is rejected before anything is created', function (array $admin) {
    expect(fn () => app(ProvisionTenant::class)->handle(
        new ProvisionTenantData(...['name' => 'Curaduría Incompleta', 'subdomain' => 'cuincompleta', ...$admin]),
    ))->toThrow(ValidationException::class);

    expect(Tenant::count())->toBe(0);
})->with([
    'name only' => [['adminName' => 'Ana Pérez']],
    'email only' => [['adminEmail' => 'ana@cu.co']],
    'bad email' => [['adminName' => 'Ana Pérez', 'adminEmail' => 'no-es-un-correo']],
]);

test('each curaduría has its own employees: A never sees B', function () {
    $provision = app(ProvisionTenant::class);
    $a = $provision->handle(new ProvisionTenantData(name: 'Curaduría A', subdomain: 'cuempa', adminName: 'Admin A', adminEmail: 'admin@a.co'));
    $b = $provision->handle(new ProvisionTenantData(name: 'Curaduría B', subdomain: 'cuempb', adminName: 'Admin B', adminEmail: 'admin@b.co'));

    expect($a->run(fn () => Employee::query()->pluck('email')->all()))->toBe(['admin@a.co'])
        ->and($b->run(fn () => Employee::query()->pluck('email')->all()))->toBe(['admin@b.co']);
});

test('the same person can have an account in two curadurías, unaware of each other', function () {
    $provision = app(ProvisionTenant::class);
    $a = $provision->handle(new ProvisionTenantData(name: 'Curaduría A', subdomain: 'cuduopa', adminName: 'Ana Pérez', adminEmail: 'ana@mail.co'));
    $b = $provision->handle(new ProvisionTenantData(name: 'Curaduría B', subdomain: 'cuduopb', adminName: 'Ana Pérez', adminEmail: 'ana@mail.co'));

    expect($a->run(fn () => Employee::query()->count()))->toBe(1)
        ->and($b->run(fn () => Employee::query()->count()))->toBe(1);
});

test('employees and roles live only in the tenant database, never in the central one', function () {
    app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Curaduría Central', subdomain: 'cucentral', adminName: 'Ana Pérez', adminEmail: 'ana@c.co'));

    $central = config('tenancy.database.central_connection');

    foreach (['employees', 'roles', 'model_has_roles', 'permissions'] as $table) {
        expect(Schema::connection($central)->hasTable($table))->toBeFalse();
    }
    expect(DB::connection($central)->getDatabaseName())->toBe(config("database.connections.{$central}.database"));
});
