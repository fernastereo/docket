<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Models\Employee;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

function newEmployee(array $overrides = []): CreateEmployeeData
{
    return new CreateEmployeeData(...array_merge([
        'name' => 'Ana Pérez',
        'email' => 'ana@curaduria.test',
        'roles' => [EmployeeRole::Architect],
    ], $overrides));
}

test('creates an active employee without a password and with its roles', function () {
    $employee = app(CreateEmployee::class)->handle(newEmployee(['roles' => [EmployeeRole::Architect, EmployeeRole::Curator]]));

    expect($employee->status)->toBe(EmployeeStatus::Active)
        ->and($employee->password)->toBeNull()
        ->and($employee->hasRole(EmployeeRole::Architect->value))->toBeTrue()
        ->and($employee->hasRole(EmployeeRole::Curator->value))->toBeTrue()
        ->and($employee->hasRole(EmployeeRole::Admin->value))->toBeFalse();
});

test('the email is stored in lowercase', function () {
    $employee = app(CreateEmployee::class)->handle(newEmployee(['email' => '  Ana@Curaduria.TEST ']));

    expect($employee->email)->toBe('ana@curaduria.test');
});

test('an account without a password is not an account that can sign in by itself', function () {
    $employee = app(CreateEmployee::class)->handle(newEmployee());

    expect($employee->password)->toBeNull()
        ->and($employee->isActive())->toBeTrue();
});

test('the email is unique within the curaduría, whatever its case', function () {
    app(CreateEmployee::class)->handle(newEmployee());

    expect(fn () => app(CreateEmployee::class)->handle(newEmployee(['email' => 'ANA@curaduria.test'])))
        ->toThrow(ValidationException::class);
    expect(Employee::query()->count())->toBe(1);
});

test('rejects invalid data and creates nothing', function (array $overrides) {
    expect(fn () => app(CreateEmployee::class)->handle(newEmployee($overrides)))
        ->toThrow(ValidationException::class);

    expect(Employee::query()->count())->toBe(0);
})->with([
    'no name' => [['name' => '']],
    'bad email' => [['email' => 'no-es-un-correo']],
    'no roles' => [['roles' => []]],
    'valid until before valid from' => [[
        'validFrom' => new DateTimeImmutable('2026-12-01'),
        'validUntil' => new DateTimeImmutable('2026-11-01'),
    ]],
]);

test('the account is active only within its validity dates', function () {
    $this->travelTo(now()->setDate(2026, 11, 15));

    $current = app(CreateEmployee::class)->handle(newEmployee([
        'email' => 'vigente@curaduria.test',
        'validFrom' => new DateTimeImmutable('2026-11-15'),
        'validUntil' => new DateTimeImmutable('2026-11-15'),
    ]));
    $future = app(CreateEmployee::class)->handle(newEmployee([
        'email' => 'futuro@curaduria.test',
        'validFrom' => new DateTimeImmutable('2026-11-16'),
    ]));
    $expired = app(CreateEmployee::class)->handle(newEmployee([
        'email' => 'vencido@curaduria.test',
        'validFrom' => new DateTimeImmutable('2026-01-01'),
        'validUntil' => new DateTimeImmutable('2026-11-14'),
    ]));

    expect($current->isActive())->toBeTrue()
        ->and($future->isActive())->toBeFalse()
        ->and($expired->isActive())->toBeFalse();
});

test('a suspended or terminated account is never active', function (EmployeeStatus $status) {
    $employee = app(CreateEmployee::class)->handle(newEmployee());
    $employee->forceFill(['status' => $status])->save();

    expect($employee->refresh()->isActive())->toBeFalse();
})->with([EmployeeStatus::Suspended, EmployeeStatus::Terminated]);

test('the password is never exposed when the employee is serialized', function () {
    $employee = app(CreateEmployee::class)->handle(newEmployee());
    $employee->forceFill(['password' => 'una-contraseña-de-prueba-larga'])->save();

    expect($employee->refresh()->toArray())->not->toHaveKey('password')
        ->and($employee->password)->not->toBe('una-contraseña-de-prueba-larga');
});
