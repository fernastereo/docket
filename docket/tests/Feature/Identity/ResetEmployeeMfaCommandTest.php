<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Models\Employee;
use App\Platform\Rules\TenantSubdomain;
use App\Support\Mfa\Mfa;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

beforeEach(function () {
    $this->subdomain = 'rm'.strtolower(Str::random(6));
    $this->tenant->domains()->create(['domain' => TenantSubdomain::hostFor($this->subdomain)]);
});

afterEach(fn () => $this->tenant->domains()->delete());

function employeeWithMfa(string $email = 'ana@curaduria.test'): Employee
{
    $employee = app(CreateEmployee::class)->handle(new CreateEmployeeData('Ana Pérez', $email, [EmployeeRole::Admin]));
    $employee->forceFill([
        'password' => 'prueba-prueba-prueba',
        'mfa_secret' => 'JBSWY3DPEHPK3PXP',
        'mfa_confirmed_at' => now(),
        'mfa_last_used_timestamp' => 12345,
        'mfa_recovery_codes' => ['hash-uno', 'hash-dos'],
    ])->save();

    return $employee;
}

test('clears the second factor of that employee so it must be enrolled again, keeping the password', function () {
    $ana = employeeWithMfa();
    $beto = employeeWithMfa('beto@curaduria.test');

    $this->artisan('employees:reset-mfa', ['subdomain' => $this->subdomain, 'email' => 'ANA@curaduria.test'])
        ->expectsOutputToContain('MFA desactivado')
        ->assertSuccessful();

    $ana->refresh();
    expect($ana->hasMfaEnabled())->toBeFalse()
        ->and($ana->mfa_secret)->toBeNull()
        ->and($ana->mfa_confirmed_at)->toBeNull()
        ->and($ana->mfa_last_used_timestamp)->toBeNull()
        ->and($ana->mfa_recovery_codes)->toBeNull()
        ->and($ana->password)->not->toBeNull()
        // Otro empleado de la misma curaduría no se toca.
        ->and($beto->refresh()->hasMfaEnabled())->toBeTrue();
});

test('releases the lock of the second step', function () {
    $ana = employeeWithMfa();
    $mfa = app(Mfa::class);
    for ($i = 0; $i < (int) config('mfa.max_attempts'); $i++) {
        try {
            $mfa->fail($ana);
        } catch (ValidationException) {
        }
    }
    expect(fn () => $mfa->ensureNotLocked($ana))->toThrow(ThrottleRequestsException::class);

    $this->artisan('employees:reset-mfa', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test'])->assertSuccessful();

    $mfa->ensureNotLocked($ana);
    expect(true)->toBeTrue();
});

test('fails for an unknown curaduría or employee and touches nothing', function () {
    $ana = employeeWithMfa();

    $this->artisan('employees:reset-mfa', ['subdomain' => 'no-existe', 'email' => 'ana@curaduria.test'])
        ->expectsOutputToContain('No existe una curaduría')
        ->assertFailed();
    $this->artisan('employees:reset-mfa', ['subdomain' => $this->subdomain, 'email' => 'nadie@curaduria.test'])
        ->expectsOutputToContain('No existe un empleado')
        ->assertFailed();

    expect($ana->refresh()->hasMfaEnabled())->toBeTrue();
});
