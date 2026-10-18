<?php

declare(strict_types=1);

use App\Platform\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesTenantDatabase;

// Prueba el arnés que usarán los tests de dominio (UsesTenantDatabase): un tenant
// de pruebas compartido por proceso y una transacción revertida por test.
uses(UsesTenantDatabase::class);

test('runs inside the shared test tenant database, with the test prefix', function () {
    $database = DB::selectOne('select current_database() as name')->name;

    expect($database)->toBe('tenanttest_'.$this->tenant->id)
        ->and(tenant()?->getTenantKey())->toBe($this->tenant->getTenantKey());
});

test('the tenant is shared across tests of the run, not provisioned again', function () {
    $GLOBALS['harness_first_tenant_id'] ??= $this->tenant->id;

    expect($this->tenant->id)->toBe($GLOBALS['harness_first_tenant_id']);
});

test('writes and DDL are visible inside the test (part 1 of the rollback check)', function () {
    Schema::create('harness_probe', function (Blueprint $table) {
        $table->id();
        $table->string('note');
    });
    DB::table('harness_probe')->insert(['note' => 'solo vive dentro de este test']);

    expect(DB::table('harness_probe')->count())->toBe(1);
});

// Depende de correr después del anterior (Pest respeta el orden del archivo): si
// la transacción no se revirtiera, la tabla seguiría existiendo.
test('everything was rolled back after the previous test (part 2)', function () {
    expect(Schema::hasTable('harness_probe'))->toBeFalse();
});

test('domain tests do not leave tenants behind in the central catalog', function () {
    expect(Tenant::query()->count())->toBeLessThanOrEqual(1);
});
