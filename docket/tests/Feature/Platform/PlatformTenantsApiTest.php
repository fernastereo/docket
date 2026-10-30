<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Domain\Identity\Mail\EmployeePasswordResetMail;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Enums\TenantStatus;
use App\Platform\Models\PlatformUser;
use App\Platform\Models\Tenant;
use Database\Factories\PlatformUserFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

/**
 * Cuerpo válido del alta; cada test pisa lo que le interesa.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tenantPayload(array $overrides = []): array
{
    return [
        'name' => 'Curaduría Uno',
        'subdomain' => 'cuuno',
        'admin_name' => 'Ana Admin',
        'admin_email' => 'ana@cuuno.test',
        ...$overrides,
    ];
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
        ->postJson(panelUrl('/tenants'), tenantPayload())
        ->assertUnauthorized();

    expect(Tenant::count())->toBe(0);
});

test('lists the tenants with host and status, never database details', function () {
    asPlatformUser();
    $this->postJson(panelUrl('/tenants'), tenantPayload())->assertCreated();

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

    $response = $this->postJson(panelUrl('/tenants'), tenantPayload());

    $response->assertCreated()
        ->assertJsonPath('data.host', 'cuuno.docket.test')
        ->assertJsonPath('data.status', 'demo');

    $tenant = Tenant::findOrFail($response->json('data.id'));
    expect($tenant->status)->toBe(TenantStatus::Demo)
        ->and(DB::selectOne('select 1 as found from pg_database where datname = ?', [$tenant->database()->getName()]))->not->toBeNull();
});

test('can create the tenant directly as active', function () {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), tenantPayload(['active' => true]))
        ->assertCreated()
        ->assertJsonPath('data.status', 'active');
});

test('creates the first administrator and invites them by email', function () {
    Mail::fake();
    asPlatformUser();

    $response = $this->postJson(panelUrl('/tenants'), tenantPayload())->assertCreated();

    $tenant = Tenant::findOrFail($response->json('data.id'));
    $tenant->run(function () {
        $admin = Employee::query()->where('email', 'ana@cuuno.test')->firstOrFail();
        expect($admin->name)->toBe('Ana Admin')
            ->and($admin->password)->toBeNull()
            ->and($admin->hasRole(EmployeeRole::Admin->value))->toBeTrue();
    });
    Mail::assertQueued(EmployeeInvitationMail::class, fn ($mail) => $mail->hasTo('ana@cuuno.test'));
});

test('the list shows each curaduría\'s administrator and whether their invitation is still pending', function () {
    Mail::fake();
    asPlatformUser();
    $id = $this->postJson(panelUrl('/tenants'), tenantPayload())->assertCreated()->json('data.id');

    $this->getJson(panelUrl('/tenants'))
        ->assertJsonPath('data.0.admin.name', 'Ana Admin')
        ->assertJsonPath('data.0.admin.email', 'ana@cuuno.test')
        ->assertJsonPath('data.0.admin.invitation_pending', true);

    Tenant::findOrFail($id)->run(fn () => Employee::firstAdmin()->forceFill(['password' => 'una-frase-larga-y-segura'])->save());

    $response = $this->getJson(panelUrl('/tenants'))->assertJsonPath('data.0.admin.invitation_pending', false);
    expect(json_encode($response->json()))->not->toContain('una-frase-larga')->not->toContain('$argon');
});

test('a curaduría without an administrator lists none', function () {
    asPlatformUser();
    app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Curaduría Sola', subdomain: 'cusola'));

    $this->getJson(panelUrl('/tenants'))->assertJsonPath('data.0.admin', null);
});

function createTenantWithAdmin(): string
{
    return test()->postJson(panelUrl('/tenants'), tenantPayload())->assertCreated()->json('data.id');
}

function setAdminPassword(string $tenantId, string $password = 'una-frase-larga-y-segura'): void
{
    Tenant::findOrFail($tenantId)->run(fn () => Employee::firstAdmin()->forceFill(['password' => $password])->save());
}

test('sending the link to an administrator without password re-invites them and voids the previous link', function () {
    Mail::fake();
    asPlatformUser();
    $id = createTenantWithAdmin();
    Mail::assertQueued(EmployeeInvitationMail::class, 1);

    $this->postJson(panelUrl("/tenants/{$id}/admin/link"))->assertNoContent();

    Mail::assertQueued(EmployeeInvitationMail::class, 2);
    Mail::assertNotQueued(EmployeePasswordResetMail::class);
    Tenant::findOrFail($id)->run(fn () => expect(EmployeeInvitation::query()->count())->toBe(1));
});

test('sending the link to an administrator with a password sends a recovery link instead', function () {
    Mail::fake();
    asPlatformUser();
    $id = createTenantWithAdmin();
    setAdminPassword($id);

    $this->postJson(panelUrl("/tenants/{$id}/admin/link"))->assertNoContent();

    Mail::assertQueued(EmployeePasswordResetMail::class, fn ($mail) => $mail->hasTo('ana@cuuno.test') && str_contains($mail->url, '/invitation/'));
    Mail::assertQueued(EmployeeInvitationMail::class, 1);
});

test('a suspended administrator gets no link', function () {
    Mail::fake();
    asPlatformUser();
    $id = createTenantWithAdmin();
    setAdminPassword($id);
    Tenant::findOrFail($id)->run(fn () => Employee::firstAdmin()->forceFill(['status' => EmployeeStatus::Suspended])->save());

    $this->postJson(panelUrl("/tenants/{$id}/admin/link"))->assertStatus(422)->assertJsonValidationErrors('email');

    Mail::assertNotQueued(EmployeePasswordResetMail::class);
});

test('the operator can set the administrator\'s password, which then works and voids pending links', function () {
    Mail::fake();
    asPlatformUser();
    $id = createTenantWithAdmin();

    $this->putJson(panelUrl("/tenants/{$id}/admin/password"), [
        'password' => 'una-frase-larga-y-segura',
        'password_confirmation' => 'una-frase-larga-y-segura',
    ])->assertNoContent();

    Tenant::findOrFail($id)->run(function () {
        expect(Hash::check('una-frase-larga-y-segura', Employee::firstAdmin()->password))->toBeTrue()
            ->and(EmployeeInvitation::query()->whereNull('used_at')->count())->toBe(0);
    });
    $this->getJson(panelUrl('/tenants'))->assertJsonPath('data.0.admin.invitation_pending', false);
});

test('setting the password logs who did it, never the password', function () {
    asPlatformUser();
    $id = createTenantWithAdmin();
    Log::spy();

    $this->putJson(panelUrl("/tenants/{$id}/admin/password"), [
        'password' => 'una-frase-larga-y-segura',
        'password_confirmation' => 'una-frase-larga-y-segura',
    ])->assertNoContent();

    Log::shouldHaveReceived('notice')->once()->withArgs(fn ($message, $context) => $context['tenant_id'] === $id
        && isset($context['platform_user_id'])
        && ! str_contains(json_encode($context), 'una-frase'));
});

test('the operator\'s password must meet the policy and be confirmed', function (array $body) {
    asPlatformUser();
    $id = createTenantWithAdmin();

    $this->putJson(panelUrl("/tenants/{$id}/admin/password"), $body)
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');

    Tenant::findOrFail($id)->run(fn () => expect(Employee::firstAdmin()->password)->toBeNull());
})->with([
    'too short' => [['password' => 'corta', 'password_confirmation' => 'corta']],
    'not confirmed' => [['password' => 'una-frase-larga-y-segura', 'password_confirmation' => 'otra-distinta-larga']],
    'missing' => [[]],
]);

test('a read-only curaduría does not accept a password change', function () {
    asPlatformUser();
    $id = createTenantWithAdmin();
    Tenant::findOrFail($id)->forceFill(['status' => TenantStatus::Suspended])->save();

    $this->putJson(panelUrl("/tenants/{$id}/admin/password"), [
        'password' => 'una-frase-larga-y-segura',
        'password_confirmation' => 'una-frase-larga-y-segura',
    ])->assertStatus(422)->assertJsonValidationErrors('tenant');
});

test('the admin actions need an administrator and a ready curaduría', function () {
    asPlatformUser();
    $tenant = app(ProvisionTenant::class)->handle(new ProvisionTenantData(name: 'Curaduría Sola', subdomain: 'cusola'));

    $this->postJson(panelUrl("/tenants/{$tenant->id}/admin/link"))->assertStatus(422)->assertJsonValidationErrors('tenant');

    $tenant->forceFill(['status' => TenantStatus::Failed])->save();
    $this->postJson(panelUrl("/tenants/{$tenant->id}/admin/link"))->assertStatus(422)->assertJsonValidationErrors('tenant');
});

test('the admin actions need a platform session and an existing curaduría', function () {
    $unknown = '00000000-0000-0000-0000-000000000000';
    $body = ['password' => 'una-frase-larga-y-segura', 'password_confirmation' => 'una-frase-larga-y-segura'];

    $this->withHeaders(['Origin' => 'https://'.panelHost()])->postJson(panelUrl("/tenants/{$unknown}/admin/link"))->assertUnauthorized();
    $this->putJson(panelUrl("/tenants/{$unknown}/admin/password"), $body)->assertUnauthorized();

    asPlatformUser();
    $this->postJson(panelUrl("/tenants/{$unknown}/admin/link"))->assertNotFound();
    $this->putJson(panelUrl("/tenants/{$unknown}/admin/password"), $body)->assertNotFound();
});

test('lowercases the subdomain the operator typed', function () {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), tenantPayload(['subdomain' => ' CuUno ']))
        ->assertCreated()
        ->assertJsonPath('data.host', 'cuuno.docket.test');
});

test('invalid data is a 422 and leaves nothing behind', function (array $payload, string $field) {
    asPlatformUser();

    $this->postJson(panelUrl('/tenants'), tenantPayload($payload))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);

    expect(Tenant::count())->toBe(0);
})->with([
    'missing name' => [['name' => null], 'name'],
    'missing subdomain' => [['subdomain' => null], 'subdomain'],
    'reserved subdomain' => [['subdomain' => 'admin'], 'subdomain'],
    'malformed subdomain' => [['subdomain' => 'no valido!'], 'subdomain'],
    'name too short' => [['name' => 'ab'], 'name'],
    'missing admin name' => [['admin_name' => null], 'admin_name'],
    'missing admin email' => [['admin_email' => null], 'admin_email'],
    'admin name too short' => [['admin_name' => 'Al'], 'admin_name'],
    'malformed admin email' => [['admin_email' => 'no-es-un-correo'], 'admin_email'],
]);

test('a subdomain already taken is a 422', function () {
    asPlatformUser();
    $this->postJson(panelUrl('/tenants'), tenantPayload())->assertCreated();

    $this->postJson(panelUrl('/tenants'), tenantPayload(['name' => 'Otra Curaduría']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('subdomain');
    expect(Tenant::count())->toBe(1);
});

test('a provisioning failure answers 500 with a safe message and leaves a failed tenant', function () {
    asPlatformUser();
    Event::listen(DatabaseCreated::class, fn () => throw new RuntimeException('detalle interno secreto'));

    $response = $this->postJson(panelUrl('/tenants'), tenantPayload());

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
