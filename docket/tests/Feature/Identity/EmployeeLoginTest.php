<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Models\Employee;
use App\Http\Middleware\EnforceEmployeeSessionLimits;
use App\Http\Middleware\RequireEmployeeMfa;
use App\Support\Mfa\BeginMfaSetup;
use App\Support\Mfa\ConfirmMfa;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

const EMPLOYEE_PASSWORD = 'prueba-prueba-prueba';

// El login del empleado vive en el subdominio de su curaduría: se le da al
// tenant compartido un dominio propio del test y se borra al terminar (la fila
// está en la central, que no participa de la transacción del test).
beforeEach(function () {
    $this->host = 'login-'.strtolower(Str::random(6)).'.'.config('platform.tenant_base_domain');
    $this->tenant->domains()->create(['domain' => $this->host]);
    config(['sanctum.stateful' => [$this->host]]);

    // Ruta de prueba protegida por el segundo factor: aún no hay rutas de negocio.
    Route::middleware(['api', InitializeTenancyByDomain::class, 'auth:employee', EnforceEmployeeSessionLimits::class, RequireEmployeeMfa::class])
        ->get('api/employee/_probe', fn () => response()->json(['ok' => true]));
});

afterEach(fn () => $this->tenant->domains()->delete());

function employeeAs(array $roles, array $overrides = []): Employee
{
    $employee = app(CreateEmployee::class)->handle(new CreateEmployeeData(
        name: 'Ana Pérez',
        email: $overrides['email'] ?? 'ana@curaduria.test',
        roles: $roles,
    ));

    $employee->forceFill(['password' => EMPLOYEE_PASSWORD, ...$overrides])->save();

    return $employee;
}

function employeeCall(string $method, string $path, array $data = []): TestResponse
{
    $host = test()->host;

    return test()->withHeaders(['Origin' => "https://{$host}", 'Referer' => "https://{$host}/"])
        ->json($method, "https://{$host}/api/employee{$path}", $data);
}

function employeeLogin(?string $password = EMPLOYEE_PASSWORD, string $email = 'ana@curaduria.test'): TestResponse
{
    return employeeCall('POST', '/login', ['email' => $email, 'password' => $password]);
}

function totp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/** El código del paso siguiente (el reloj de TOTP no sigue a `travel()`): nunca usado antes. */
function nextTotp(string $secret): string
{
    $google2fa = app(Google2FA::class);

    return $google2fa->oathTotp($secret, $google2fa->getTimestamp() + 1);
}

/** Alta completa de MFA, como la haría la persona: devuelve el secreto. */
function enrollMfa(Employee $employee): string
{
    $setup = app(BeginMfaSetup::class)->handle($employee);
    app(ConfirmMfa::class)->handle($employee, totp($setup['secret']));

    return $setup['secret'];
}

test('an employee with a role that does not require MFA signs in with the password alone', function () {
    $employee = employeeAs([EmployeeRole::Architect]);

    employeeLogin()->assertOk()
        ->assertJsonPath('data.email', 'ana@curaduria.test')
        ->assertJsonPath('data.roles', ['architect'])
        ->assertJsonPath('data.mfa_required', false)
        ->assertJsonPath('data.mfa_verified', true);

    employeeCall('GET', '/_probe')->assertOk();
    expect($employee->refresh()->last_login_at)->not->toBeNull();
});

test('every kind of failed login gets the same answer', function () {
    employeeAs([EmployeeRole::Architect]);
    $pending = app(CreateEmployee::class)->handle(new CreateEmployeeData('Luis', 'luis@curaduria.test', [EmployeeRole::Lawyer]));
    employeeAs([EmployeeRole::Engineer], ['email' => 'susp@curaduria.test', 'status' => EmployeeStatus::Suspended]);
    employeeAs([EmployeeRole::Engineer], ['email' => 'venc@curaduria.test', 'valid_until' => now()->subDay()]);
    employeeAs([EmployeeRole::Engineer], ['email' => 'futuro@curaduria.test', 'valid_from' => now()->addDay()]);

    $answers = collect([
        employeeLogin('otra-contraseña-incorrecta'),
        employeeLogin(EMPLOYEE_PASSWORD, 'nadie@curaduria.test'),
        employeeLogin(EMPLOYEE_PASSWORD, $pending->email), // invitación pendiente: sin contraseña
        employeeLogin(EMPLOYEE_PASSWORD, 'susp@curaduria.test'),
        employeeLogin(EMPLOYEE_PASSWORD, 'venc@curaduria.test'),
        employeeLogin(EMPLOYEE_PASSWORD, 'futuro@curaduria.test'),
    ]);

    $answers->each->assertStatus(422);
    expect($answers->map(fn ($r) => $r->json('errors.email.0'))->unique()->all())->toHaveCount(1);
});

test('the account locks after five failed attempts, even with the right password', function () {
    employeeAs([EmployeeRole::Architect]);

    foreach (range(1, (int) config('employee.login.max_attempts')) as $ignored) {
        employeeLogin('incorrecta-incorrecta')->assertStatus(422);
    }

    employeeLogin()->assertStatus(429);
});

test('the email is matched case-insensitively', function () {
    employeeAs([EmployeeRole::Architect]);

    employeeLogin(EMPLOYEE_PASSWORD, '  ANA@Curaduria.TEST ')->assertOk();
});

test('an admin without MFA can sign in but only reach the setup', function () {
    employeeAs([EmployeeRole::Admin]);

    employeeLogin()->assertOk()
        ->assertJsonPath('data.mfa_required', true)
        ->assertJsonPath('data.mfa_verified', false);

    employeeCall('GET', '/me')->assertOk();
    employeeCall('GET', '/_probe')->assertForbidden()->assertJsonPath('mfa', 'setup_required');
});

test('an admin completes the setup and gets in with the recovery codes issued once', function () {
    employeeAs([EmployeeRole::Admin]);
    employeeLogin()->assertOk();

    $setup = employeeCall('POST', '/mfa/setup')->assertOk()->json('data');
    expect($setup['qr'])->toStartWith('data:image/svg+xml;base64,');

    employeeCall('POST', '/mfa/confirm', ['code' => totp($setup['secret'])])->assertOk()
        ->assertJsonCount(8, 'data.recovery_codes')
        ->assertJsonPath('data.user.mfa_enabled', true);

    employeeCall('GET', '/_probe')->assertOk();
});

test('an enrolled admin must verify the code after every login', function () {
    $employee = employeeAs([EmployeeRole::Curator]);
    $secret = enrollMfa($employee);

    employeeLogin()->assertOk()->assertJsonPath('data.mfa_verified', false);
    employeeCall('GET', '/_probe')->assertForbidden()->assertJsonPath('mfa', 'verification_required');

    employeeCall('POST', '/mfa/verify', ['code' => '000000'])->assertStatus(422);
    employeeCall('GET', '/_probe')->assertForbidden();

    // El código con el que se activó ya está gastado: hace falta uno nuevo.
    employeeCall('POST', '/mfa/verify', ['code' => nextTotp($secret)])->assertOk()->assertJsonPath('data.mfa_verified', true);
    employeeCall('GET', '/_probe')->assertOk();
});

test('a role with optional MFA is still asked for the code once it has enabled it', function () {
    $employee = employeeAs([EmployeeRole::Architect]);
    enrollMfa($employee);

    employeeLogin()->assertOk()->assertJsonPath('data.mfa_verified', false);
    employeeCall('GET', '/_probe')->assertForbidden()->assertJsonPath('mfa', 'verification_required');
});

test('an optional-MFA employee can turn it on from a normal session', function () {
    employeeAs([EmployeeRole::Architect]);
    employeeLogin()->assertOk();

    $setup = employeeCall('POST', '/mfa/setup')->assertOk()->json('data');
    employeeCall('POST', '/mfa/confirm', ['code' => totp($setup['secret'])])->assertOk();
    employeeCall('GET', '/_probe')->assertOk();
});

test('the session expires after 30 minutes of inactivity', function () {
    employeeAs([EmployeeRole::Architect]);
    employeeLogin()->assertOk();

    $this->travel(29)->minutes();
    employeeCall('GET', '/me')->assertOk();

    $this->travel(31)->minutes();
    employeeCall('GET', '/me')->assertUnauthorized();
});

test('the session ends after 12 hours even if it is always active', function () {
    employeeAs([EmployeeRole::Architect]);
    employeeLogin()->assertOk();

    // Pasos de 29 min: nunca llega a los 30 de inactividad.
    foreach (range(1, 24) as $ignored) {
        $this->travel(29)->minutes();
        employeeCall('GET', '/me')->assertOk();
    }

    $this->travel(29)->minutes(); // 12 h 5 min desde el login
    employeeCall('GET', '/me')->assertUnauthorized();
});

test('suspending an employee ends their open session on the next request', function () {
    $employee = employeeAs([EmployeeRole::Architect]);
    employeeLogin()->assertOk();

    $employee->forceFill(['status' => EmployeeStatus::Suspended])->save();
    app('auth')->forgetGuards(); // en producción cada petición carga al empleado de nuevo

    employeeCall('GET', '/me')->assertUnauthorized();
});

test('logout closes the session', function () {
    employeeAs([EmployeeRole::Architect]);
    employeeLogin()->assertOk();

    employeeCall('POST', '/logout')->assertNoContent();
    employeeCall('GET', '/me')->assertUnauthorized();
});

test('the employee endpoints do not exist on the central domain', function () {
    $central = config('tenancy.central_domains')[0];

    $this->postJson("https://{$central}/api/employee/login", ['email' => 'a@b.co', 'password' => 'x'])
        ->assertStatus(404);
});

test('the resource never exposes secrets', function () {
    employeeAs([EmployeeRole::Architect]);

    $json = employeeLogin()->assertOk()->json('data');

    expect($json)->not->toHaveKeys(['password', 'mfa_secret', 'mfa_recovery_codes']);
});
