<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\Actions\SetEmployeePassword;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Domain\Identity\Mail\EmployeePasswordResetMail;
use App\Domain\Identity\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

const RESET_PASSWORD = 'prueba-prueba-prueba';

// El enlace y los endpoints viven en el subdominio de la curaduría: el tenant
// compartido recibe uno propio del test y se borra al terminar.
beforeEach(function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    Mail::fake();
    $this->withoutDefer(); // el trabajo posterior a la respuesta corre dentro del test

    $this->host = 'fp-'.strtolower(Str::random(6)).'.'.config('platform.tenant_base_domain');
    $this->tenant->domains()->create(['domain' => $this->host]);
    config(['sanctum.stateful' => [$this->host], 'platform.tenant_url_port' => null]);
});

afterEach(fn () => $this->tenant->domains()->delete());

function resetEmployee(string $email = 'ana@curaduria.test', bool $withPassword = true): Employee
{
    $employee = app(CreateEmployee::class)->handle(new CreateEmployeeData('Ana Pérez', $email, [EmployeeRole::Architect]));

    if ($withPassword) {
        $employee->forceFill(['password' => RESET_PASSWORD])->save();
    }

    return $employee;
}

function resetCall(string $method, string $path, array $data = []): TestResponse
{
    $host = test()->host;

    return test()->withHeaders(['Origin' => "https://{$host}", 'Referer' => "https://{$host}/"])
        ->json($method, "https://{$host}/api/employee{$path}", $data);
}

function forgotPassword(string $email = 'ana@curaduria.test'): TestResponse
{
    return resetCall('POST', '/forgot-password', ['email' => $email]);
}

test('an employee with a password gets a recovery link by email', function () {
    resetEmployee();

    forgotPassword()->assertStatus(202);

    Mail::assertQueued(EmployeePasswordResetMail::class, fn (EmployeePasswordResetMail $mail) => $mail->hasTo('ana@curaduria.test')
        && str_contains($mail->url, '/invitation/'));
    Mail::assertNotQueued(EmployeeInvitationMail::class);
});

test('an employee who never set a password gets the invitation again', function () {
    resetEmployee(withPassword: false);

    forgotPassword()->assertStatus(202);

    Mail::assertQueued(EmployeeInvitationMail::class, fn (EmployeeInvitationMail $mail) => $mail->hasTo('ana@curaduria.test'));
    Mail::assertNotQueued(EmployeePasswordResetMail::class);
});

test('the answer is the same whether the account exists, is suspended or does not exist', function () {
    resetEmployee();
    resetEmployee('susp@curaduria.test')->forceFill(['status' => EmployeeStatus::Suspended])->save();

    $known = forgotPassword('ana@curaduria.test');
    $suspended = forgotPassword('susp@curaduria.test');
    $unknown = forgotPassword('nadie@curaduria.test');

    expect($known->status())->toBe(202)
        ->and($suspended->status())->toBe(202)
        ->and($unknown->status())->toBe(202)
        ->and($suspended->getContent())->toBe($known->getContent())
        ->and($unknown->getContent())->toBe($known->getContent())
        ->and($known->json('message'))->toBe('Si ese correo tiene una cuenta en esta curaduría, te enviamos un enlace para elegir una contraseña nueva.');
    Mail::assertQueued(EmployeePasswordResetMail::class, 1);
});

test('the email is matched ignoring case and surrounding spaces', function () {
    resetEmployee();

    forgotPassword('  ANA@Curaduria.TEST ')->assertStatus(202);

    Mail::assertQueued(EmployeePasswordResetMail::class, fn (EmployeePasswordResetMail $mail) => $mail->hasTo('ana@curaduria.test'));
});

test('an account gets at most three emails per window, and the limit is invisible to the caller', function () {
    resetEmployee();

    foreach (range(1, 5) as $_) {
        forgotPassword()->assertStatus(202);
    }

    Mail::assertQueued(EmployeePasswordResetMail::class, 3);
});

test('requests for accounts that do not exist count against the same limit', function () {
    foreach (range(1, 3) as $_) {
        forgotPassword('nadie@curaduria.test')->assertStatus(202);
    }
    resetEmployee('nadie@curaduria.test');

    // Si el límite no contara a las cuentas inexistentes, este sí enviaría correo
    // y se sabría qué correos existen.
    forgotPassword('nadie@curaduria.test')->assertStatus(202);

    Mail::assertNothingQueued();
});

test('a malformed or missing email is a validation error', function () {
    resetCall('POST', '/forgot-password', ['email' => 'no-es-un-correo'])->assertStatus(422)->assertJsonValidationErrors('email');
    resetCall('POST', '/forgot-password')->assertStatus(422)->assertJsonValidationErrors('email');
});

test('the endpoint does not exist on the central domain', function () {
    $central = config('tenancy.central_domains')[0];

    $this->postJson("https://{$central}/api/employee/forgot-password", ['email' => 'a@b.co'])->assertStatus(404);
});

test('the recovery link chooses a new password, closes the sessions opened before and keeps the MFA', function () {
    $employee = resetEmployee();
    $employee->forceFill(['mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_confirmed_at' => now()])->save();

    // Una sesión abierta antes de pedir la recuperación (la de quien tenía la cuenta comprometida).
    resetCall('POST', '/login', ['email' => 'ana@curaduria.test', 'password' => RESET_PASSWORD])->assertOk();
    resetCall('GET', '/me')->assertOk();

    forgotPassword()->assertStatus(202);
    $url = '';
    Mail::assertQueued(EmployeePasswordResetMail::class, function (EmployeePasswordResetMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });
    $token = Str::afterLast($url, '/invitation/');

    $this->travel(10)->seconds();
    $new = 'contraseña-nueva-2026';
    resetCall('POST', "/invitation/{$token}/accept", ['password' => $new, 'password_confirmation' => $new])->assertNoContent();

    app('auth')->forgetGuards(); // en producción cada petición carga al empleado de nuevo
    resetCall('GET', '/me')->assertUnauthorized();
    expect($employee->refresh()->hasMfaEnabled())->toBeTrue()
        ->and(Hash::check($new, $employee->password))->toBeTrue();

    // Con la contraseña nueva entra otra vez (y la vieja ya no sirve).
    resetCall('POST', '/login', ['email' => 'ana@curaduria.test', 'password' => RESET_PASSWORD])->assertStatus(422);
    resetCall('POST', '/login', ['email' => 'ana@curaduria.test', 'password' => $new])->assertOk();
    resetCall('GET', '/me')->assertOk();
});

test('changing a password stamps when, and a session opened afterwards is not affected', function () {
    $employee = resetEmployee();
    expect($employee->password_changed_at)->toBeNull();

    app(SetEmployeePassword::class)->handle($employee, 'otra-contraseña-2026');

    expect($employee->refresh()->password_changed_at)->not->toBeNull();

    resetCall('POST', '/login', ['email' => 'ana@curaduria.test', 'password' => 'otra-contraseña-2026'])->assertOk();
    resetCall('GET', '/me')->assertOk();
});
