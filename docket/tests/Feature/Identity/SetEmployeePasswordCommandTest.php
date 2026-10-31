<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Models\Employee;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

// El comando entra a la curaduría por su subdominio: se le da al tenant
// compartido uno propio del test y se borra al terminar (fila de la central).
beforeEach(function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

    $this->subdomain = 'pw'.strtolower(Str::random(6));
    $this->tenant->domains()->create(['domain' => TenantSubdomain::hostFor($this->subdomain)]);

    app(CreateEmployee::class)->handle(new CreateEmployeeData('Ana Pérez', 'ana@curaduria.test', [EmployeeRole::Architect]));
});

afterEach(fn () => $this->tenant->domains()->delete());

test('sets the password of an employee of that curaduría', function () {
    $this->artisan('employees:set-password', ['subdomain' => $this->subdomain, 'email' => 'ANA@curaduria.test'])
        ->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'empleado-prueba-7h2q-Wx91')
        ->expectsQuestion('Repite la contraseña', 'empleado-prueba-7h2q-Wx91')
        ->expectsOutputToContain('Contraseña fijada')
        ->assertSuccessful();

    $employee = Employee::where('email', 'ana@curaduria.test')->first();

    expect(Hash::check('empleado-prueba-7h2q-Wx91', $employee->password))->toBeTrue();
});

test('rejects a password that is too short', function () {
    $this->artisan('employees:set-password', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test'])
        ->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'corta')
        ->expectsQuestion('Repite la contraseña', 'corta')
        ->assertFailed();

    expect(Employee::where('email', 'ana@curaduria.test')->first()->password)->toBeNull();
});

test('rejects passwords that do not match', function () {
    $this->artisan('employees:set-password', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test'])
        ->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'empleado-prueba-7h2q-Wx91')
        ->expectsQuestion('Repite la contraseña', 'otra-distinta-1234567')
        ->expectsOutputToContain('no coinciden')
        ->assertFailed();

    expect(Employee::where('email', 'ana@curaduria.test')->first()->password)->toBeNull();
});

test('fails for an unknown curaduría or employee', function () {
    $this->artisan('employees:set-password', ['subdomain' => 'no-existe', 'email' => 'ana@curaduria.test'])
        ->expectsOutputToContain('No existe una curaduría')
        ->assertFailed();

    $this->artisan('employees:set-password', ['subdomain' => $this->subdomain, 'email' => 'nadie@curaduria.test'])
        ->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'empleado-prueba-7h2q-Wx91')
        ->expectsQuestion('Repite la contraseña', 'empleado-prueba-7h2q-Wx91')
        ->expectsOutputToContain('No existe un empleado')
        ->assertFailed();
});
