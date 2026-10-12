<?php

declare(strict_types=1);

use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Obligatorio (ADR-011): un test de aislamiento en rojo bloquea el deploy
// (ADR-017). Prueba el MECANISMO de aislamiento con dos tenants reales (dos bases
// físicas) y una tabla sonda creada por el propio test, sin depender del esquema
// del núcleo. Cuando existan las tablas del negocio, cualquier test de dominio
// que escriba en un tenant queda además cubierto por UsesTenantDatabase.
uses(DatabaseMigrations::class);

beforeEach(fn () => config(['platform.tenant_base_domain' => 'docket.test']));

afterEach(fn () => Tenant::query()->get()->each->delete());

function provisionIsolationPair(): array
{
    $provision = app(ProvisionTenant::class);

    $a = $provision->handle(new ProvisionTenantData(name: 'Curaduría A', subdomain: 'cuaislaa'));
    $b = $provision->handle(new ProvisionTenantData(name: 'Curaduría B', subdomain: 'cuaislab'));

    foreach ([$a, $b] as $tenant) {
        $tenant->run(fn () => Schema::create('isolation_probe', function (Blueprint $table) {
            $table->id();
            $table->string('owner');
        }));
    }

    return [$a, $b];
}

test('tenant A never sees tenant B\'s data', function () {
    [$a, $b] = provisionIsolationPair();

    $a->run(fn () => DB::table('isolation_probe')->insert(['owner' => 'A']));
    $b->run(fn () => DB::table('isolation_probe')->insert(['owner' => 'B1']));
    $b->run(fn () => DB::table('isolation_probe')->insert(['owner' => 'B2']));

    expect($a->run(fn () => DB::table('isolation_probe')->pluck('owner')->all()))->toBe(['A'])
        ->and($b->run(fn () => DB::table('isolation_probe')->pluck('owner')->all()))->toBe(['B1', 'B2']);
});

test('tenant data never lands in the central database', function () {
    [$a] = provisionIsolationPair();

    $a->run(fn () => DB::table('isolation_probe')->insert(['owner' => 'A']));

    $central = config('tenancy.database.central_connection');

    expect(Schema::connection($central)->hasTable('isolation_probe'))->toBeFalse()
        ->and(config('database.default'))->toBe($central);
});

test('each tenant lives in its own physical database and the connection follows the tenant', function () {
    [$a, $b] = provisionIsolationPair();

    $current = fn () => DB::selectOne('select current_database() as name')->name;

    $central = $current();

    tenancy()->initialize($a);
    $inA = $current();

    tenancy()->initialize($b);
    $inB = $current();

    tenancy()->end();

    expect($inA)->toBe('tenanttest_'.$a->id)
        ->and($inB)->toBe('tenanttest_'.$b->id)
        ->and($inA)->not->toBe($inB)
        ->and($current())->toBe($central);
});
