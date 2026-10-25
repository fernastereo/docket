<?php

declare(strict_types=1);

use App\Platform\Enums\TenantStatus;
use App\Platform\Models\PlatformUser;
use App\Platform\Models\Tenant;
use Database\Factories\PlatformUserFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FA\Google2FA;
use Stancl\Tenancy\Events\DatabaseCreated;

// Crear una curaduría crea una base física: DatabaseMigrations, no RefreshDatabase.
uses(DatabaseMigrations::class);

afterEach(fn () => Tenant::query()->get()->each->delete());

function panelHost(): string
{
    return config('tenancy.central_domains')[0];
}

function asPlatformUser(): PlatformUser
{
    config(['platform.tenant_base_domain' => 'docket.test', 'sanctum.stateful' => [panelHost()]]);
    $user = PlatformUser::factory()->withMfa()->create();

    // Como el navegador de la SPA: todas las peticiones llevan Origin, así la
    // sesión (cookie) se mantiene entre login y llamadas siguientes.
    test()->withHeaders(['Origin' => 'https://'.panelHost()])
        ->postJson('https://'.panelHost().'/api/platform/login', [
            'email' => $user->email,
            'password' => PlatformUserFactory::PASSWORD,
        ])->assertOk();

    // Segundo factor, como lo hace la persona con su app de autenticación.
    test()->postJson(panelUrl('/mfa/verify'), [
        'code' => app(Google2FA::class)->getCurrentOtp(PlatformUserFactory::MFA_SECRET),
    ])->assertOk();

    return $user;
}

function panelUrl(string $path): string
{
    return 'https://'.panelHost().'/api/platform'.$path;
}

test('the tenant list requires a platform session', function () {
    $this->withHeaders(['Origin' => 'https://'.panelHost()])
        ->getJson(panelUrl('/tenants'))
        ->assertUnauthorized();
});

test('creating a tenant requires a platform session', function () {
    $this->withHeaders(['Origin' => 'https://'.panelHost()])
        ->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno'])
        ->assertUnauthorized();

    expect(Tenant::count())->toBe(0);
});

test('lists the tenants with host and status, never database details', function () {
    asPlatformUser();
    $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno'])->assertCreated();

    $response = $this->getJson(panelUrl('/tenants'))->assertOk();

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Curaduría Uno')
        ->assertJsonPath('data.0.host', 'cuuno.docket.test')
        ->assertJsonPath('data.0.status', 'demo')
        ->assertJsonPath('meta.base_domain', 'docket.test');
    expect(json_encode($response->json()))->not->toContain('tenanttest_');
});

test('creates a demo tenant by default, with its domain and physical database', function () {
    asPlatformUser();

    $response = $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno']);

    $response->assertCreated()
        ->assertJsonPath('data.host', 'cuuno.docket.test')
        ->assertJsonPath('data.status', 'demo');

    $tenant = Tenant::findOrFail($response->json('data.id'));
    expect($tenant->status)->toBe(TenantStatus::Demo)
        ->and(DB::selectOne('select 1 as found from pg_database where datname = ?', [$tenant->database()->getName()]))->not->toBeNull();
});

test('can create the tenant directly as active', function () {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno', 'active' => true])
        ->assertCreated()
        ->assertJsonPath('data.status', 'active');
});

test('lowercases the subdomain the operator typed', function () {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => ' CuUno '])
        ->assertCreated()
        ->assertJsonPath('data.host', 'cuuno.docket.test');
});

test('invalid data is a 422 and leaves nothing behind', function (array $payload, string $field) {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);

    expect(Tenant::count())->toBe(0);
})->with([
    'missing name' => [['subdomain' => 'cuuno'], 'name'],
    'missing subdomain' => [['name' => 'Curaduría Uno'], 'subdomain'],
    'reserved subdomain' => [['name' => 'Curaduría Uno', 'subdomain' => 'admin'], 'subdomain'],
    'malformed subdomain' => [['name' => 'Curaduría Uno', 'subdomain' => 'no valido!'], 'subdomain'],
    'name too short' => [['name' => 'ab', 'subdomain' => 'cuuno'], 'name'],
]);

test('a subdomain already taken is a 422', function () {
    asPlatformUser();
    $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno'])->assertCreated();

    $this->postJson(panelUrl('/tenants'), ['name' => 'Otra Curaduría', 'subdomain' => 'cuuno'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('subdomain');
    expect(Tenant::count())->toBe(1);
});

test('a provisioning failure answers 500 with a safe message and leaves a failed tenant', function () {
    asPlatformUser();
    Event::listen(DatabaseCreated::class, fn () => throw new RuntimeException('detalle interno secreto'));

    $response = $this->postJson(panelUrl('/tenants'), ['name' => 'Curaduría Uno', 'subdomain' => 'cuuno']);

    $response->assertStatus(500);
    expect(json_encode($response->json()))->not->toContain('detalle interno secreto');
    expect(Tenant::first()->status)->toBe(TenantStatus::Failed);
});

test('a disabled platform user cannot list or create', function () {
    $user = PlatformUser::factory()->withMfa()->create();
    config(['sanctum.stateful' => [panelHost()]]);
    $this->actingAs($user, 'platform');
    $user->forceFill(['disabled_at' => now()])->save();

    $this->withHeaders(['Origin' => 'https://'.panelHost()])
        ->withSession([
            'platform.logged_in_at' => now()->getTimestamp(),
            'platform.last_activity_at' => now()->getTimestamp(),
        ])
        ->getJson(panelUrl('/tenants'))
        ->assertForbidden();
});

test('the tenant endpoints do not exist on a tenant subdomain', function () {
    $this->withHeaders(['Origin' => 'https://cuuno.docket.test'])
        ->getJson('https://cuuno.docket.test/api/platform/tenants')
        ->assertNotFound();
});
