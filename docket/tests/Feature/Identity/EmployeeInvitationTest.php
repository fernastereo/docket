<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\Actions\InviteEmployee;
use App\Domain\Identity\Actions\SendEmployeePasswordReset;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Domain\Identity\Mail\EmployeePasswordResetMail;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

// El enlace del correo apunta al dominio de la curaduría: se le da al tenant
// compartido uno propio del test y se borra al terminar (fila de la central).
beforeEach(function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    Mail::fake();

    // El enlace usa el primer dominio del tenant (el de su alta); `host` es uno
    // extra, propio del test, para las peticiones HTTP.
    $this->host = 'inv-'.strtolower(Str::random(6)).'.'.config('platform.tenant_base_domain');
    $this->tenant->domains()->create(['domain' => $this->host]);
    $this->linkHost = $this->tenant->domains()->orderBy('created_at')->first()->domain;
    config(['platform.tenant_url_port' => null]);
});

afterEach(fn () => $this->tenant->domains()->delete());

function pendingEmployee(array $overrides = []): Employee
{
    $employee = app(CreateEmployee::class)->handle(new CreateEmployeeData(
        name: 'Ana Pérez',
        email: $overrides['email'] ?? 'ana@curaduria.test',
        roles: [EmployeeRole::Architect],
    ));

    if (isset($overrides['status'])) {
        $employee->forceFill(['status' => $overrides['status']])->save();
    }

    return $employee;
}

/** Invita y devuelve el token en claro, tal como lo recibiría la persona en el correo. */
function inviteAndGetToken(Employee $employee): string
{
    app(InviteEmployee::class)->handle($employee);

    $url = '';
    Mail::assertQueued(EmployeeInvitationMail::class, function (EmployeeInvitationMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    return Str::afterLast($url, '/invitation/');
}

function invitationUrl(string $token, string $suffix = ''): string
{
    return 'https://'.test()->host.'/api/employee/invitation/'.$token.$suffix;
}

test('inviting queues one email with a link to the curaduría and stores only the token hash', function () {
    $employee = pendingEmployee();

    $token = inviteAndGetToken($employee);

    Mail::assertQueued(EmployeeInvitationMail::class, fn (EmployeeInvitationMail $mail) => $mail->hasTo('ana@curaduria.test')
        && str_starts_with($mail->url, "https://{$this->linkHost}/invitation/")
        && $mail->validDays === 7);

    $stored = EmployeeInvitation::query()->sole();
    expect($token)->toHaveLength(64)
        ->and($stored->token_hash)->toBe(hash('sha256', $token))
        ->and($stored->token_hash)->not->toContain($token)
        ->and($stored->expires_at->isFuture())->toBeTrue();
});

test('the link carries the local port when one is configured', function () {
    config(['platform.tenant_url_port' => 8443]);

    app(InviteEmployee::class)->handle(pendingEmployee());

    Mail::assertQueued(EmployeeInvitationMail::class, fn (EmployeeInvitationMail $mail) => str_starts_with($mail->url, "https://{$this->linkHost}:8443/invitation/"));
});

test('inviting again invalidates the previous link', function () {
    $employee = pendingEmployee();
    $first = inviteAndGetToken($employee);
    Mail::fake(); // nuevo registro de envíos para el segundo
    $second = inviteAndGetToken($employee);

    expect($second)->not->toBe($first)
        ->and(EmployeeInvitation::findUsable($first))->toBeNull()
        ->and(EmployeeInvitation::findUsable($second))->not->toBeNull();
});

test('an account that already has a password or is not active cannot be invited', function () {
    $withPassword = pendingEmployee();
    $withPassword->forceFill(['password' => 'prueba-prueba-prueba'])->save();
    $suspended = pendingEmployee(['email' => 'susp@curaduria.test', 'status' => EmployeeStatus::Suspended]);

    expect(fn () => app(InviteEmployee::class)->handle($withPassword))->toThrow(ValidationException::class)
        ->and(fn () => app(InviteEmployee::class)->handle($suspended))->toThrow(ValidationException::class);

    Mail::assertNothingQueued();
});

test('the link shows who is being invited', function () {
    $token = inviteAndGetToken(pendingEmployee());

    $this->getJson(invitationUrl($token))
        ->assertOk()
        ->assertExactJson(['data' => ['name' => 'Ana Pérez', 'email' => 'ana@curaduria.test']]);
});

test('an unknown, used or expired token all answer the same 404', function () {
    $employee = pendingEmployee();
    $token = inviteAndGetToken($employee);

    $this->getJson(invitationUrl(Str::random(64)))->assertNotFound();

    EmployeeInvitation::query()->update(['expires_at' => now()->subMinute()]);
    $this->getJson(invitationUrl($token))->assertNotFound();
    $this->postJson(invitationUrl($token, '/accept'), ['password' => 'x', 'password_confirmation' => 'x'])->assertNotFound();

    EmployeeInvitation::query()->update(['expires_at' => now()->addDay(), 'used_at' => now()]);
    $this->getJson(invitationUrl($token))->assertNotFound();
});

test('accepting sets the password, spends the link, and the employee can then be authenticated', function () {
    $employee = pendingEmployee();
    $token = inviteAndGetToken($employee);
    $password = 'mi-contraseña-nueva-2026';

    $this->postJson(invitationUrl($token, '/accept'), ['password' => $password, 'password_confirmation' => $password])
        ->assertNoContent();

    expect(Hash::check($password, $employee->refresh()->password))->toBeTrue()
        ->and(EmployeeInvitation::findUsable($token))->toBeNull();

    // Un enlace ya usado no sirve de nuevo.
    $this->postJson(invitationUrl($token, '/accept'), ['password' => 'otra-contraseña-2026', 'password_confirmation' => 'otra-contraseña-2026'])
        ->assertNotFound();
});

test('a password that breaks the policy is rejected and the link stays usable', function () {
    $token = inviteAndGetToken(pendingEmployee());

    $this->postJson(invitationUrl($token, '/accept'), ['password' => 'corta', 'password_confirmation' => 'corta'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->postJson(invitationUrl($token, '/accept'), ['password' => 'una-contraseña-larga-1', 'password_confirmation' => 'distinta-contraseña-1'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    expect(EmployeeInvitation::findUsable($token))->not->toBeNull();
});

test('a suspended employee cannot use a link issued before the suspension', function () {
    $employee = pendingEmployee();
    $token = inviteAndGetToken($employee);
    $employee->forceFill(['status' => EmployeeStatus::Suspended])->save();

    $password = 'mi-contraseña-nueva-2026';
    $this->postJson(invitationUrl($token, '/accept'), ['password' => $password, 'password_confirmation' => $password])
        ->assertUnprocessable();

    expect($employee->refresh()->password)->toBeNull();
});

test('the invitation endpoints do not exist on the central domain', function () {
    $central = config('tenancy.central_domains')[0];

    $this->getJson("https://{$central}/api/employee/invitation/".Str::random(64))->assertNotFound();
});

test('the email renders with the name, the curaduría, the link and the expiry', function () {
    $html = (new EmployeeInvitationMail('Ana Pérez', 'Curaduría Urbana 1', 'https://cu1.curaduria.app/invitation/abc', 7))->render();

    expect($html)->toContain('Ana Pérez')
        ->and($html)->toContain('Curaduría Urbana 1')
        ->and($html)->toContain('https://cu1.curaduria.app/invitation/abc')
        ->and($html)->toContain('7 días');
});

test('a recovery link lets an employee who has a password choose a new one through the same screen', function () {
    $employee = pendingEmployee();
    $employee->forceFill(['password' => 'prueba-prueba-prueba'])->save();

    app(SendEmployeePasswordReset::class)->handle($employee);

    $url = '';
    Mail::assertQueued(EmployeePasswordResetMail::class, function (EmployeePasswordResetMail $mail) use (&$url) {
        $url = $mail->url;

        return $mail->hasTo('ana@curaduria.test')
            && str_starts_with($mail->url, "https://{$this->linkHost}/invitation/")
            && $mail->validDays === 2;
    });
    $token = Str::afterLast($url, '/invitation/');
    $new = 'contraseña-recuperada-2026';

    // Hasta usar el enlace, la contraseña actual sigue valiendo.
    expect(Hash::check('prueba-prueba-prueba', $employee->refresh()->password))->toBeTrue();

    $this->postJson(invitationUrl($token, '/accept'), ['password' => $new, 'password_confirmation' => $new])->assertNoContent();

    expect(Hash::check($new, $employee->refresh()->password))->toBeTrue()
        ->and(EmployeeInvitation::findUsable($token))->toBeNull();
});

test('a recovery link is for accounts with a password and active, and voids the previous link', function () {
    $pending = pendingEmployee();
    $suspended = pendingEmployee(['email' => 'susp@curaduria.test', 'status' => EmployeeStatus::Suspended]);
    $suspended->forceFill(['password' => 'prueba-prueba-prueba'])->save();

    expect(fn () => app(SendEmployeePasswordReset::class)->handle($pending))->toThrow(ValidationException::class)
        ->and(fn () => app(SendEmployeePasswordReset::class)->handle($suspended))->toThrow(ValidationException::class);
    Mail::assertNothingQueued();

    $active = pendingEmployee(['email' => 'act@curaduria.test']);
    $active->forceFill(['password' => 'prueba-prueba-prueba'])->save();
    $first = EmployeeInvitation::issueFor($active, 1);
    app(SendEmployeePasswordReset::class)->handle($active);

    expect(EmployeeInvitation::findUsable($first))->toBeNull();
});

test('the recovery email renders with the name, the curaduría, the link and the expiry', function () {
    $html = (new EmployeePasswordResetMail('Ana Pérez', 'Curaduría Urbana 1', 'https://cu1.curaduria.app/invitation/abc', 2))->render();

    expect($html)->toContain('Ana Pérez')
        ->and($html)->toContain('Curaduría Urbana 1')
        ->and($html)->toContain('https://cu1.curaduria.app/invitation/abc')
        ->and($html)->toContain('2 días');
});
