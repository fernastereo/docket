<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Domain\Identity\Models\Employee;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->subdomain = 'ce'.strtolower(Str::random(6));
    $this->tenant->domains()->create(['domain' => TenantSubdomain::hostFor($this->subdomain)]);
});

afterEach(fn () => $this->tenant->domains()->delete());

test('creates an administrator by default, without a password and without sending anything', function () {
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ANA@curaduria.test', '--name' => 'Ana Pérez'])
        ->expectsOutputToContain('creado con rol: admin')
        ->expectsOutputToContain('employees:invite')
        ->assertSuccessful();

    $employee = Employee::query()->where('email', 'ana@curaduria.test')->sole();

    expect($employee->name)->toBe('Ana Pérez')
        ->and($employee->password)->toBeNull()
        ->and($employee->getRoleNames()->all())->toBe([EmployeeRole::Admin->value]);
    Mail::assertNothingQueued();
});

test('--invite sends the invitation right away', function () {
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test', '--name' => 'Ana Pérez', '--invite' => true])
        ->expectsOutputToContain('Invitación enviada')
        ->assertSuccessful();

    Mail::assertQueued(EmployeeInvitationMail::class, fn (EmployeeInvitationMail $mail) => $mail->hasTo('ana@curaduria.test'));
});

test('accepts one or more roles', function () {
    $this->artisan('employees:create', [
        'subdomain' => $this->subdomain,
        'email' => 'beto@curaduria.test',
        '--name' => 'Beto Ruiz',
        '--role' => [EmployeeRole::Architect->value, EmployeeRole::Manager->value],
    ])->assertSuccessful();

    expect(Employee::query()->where('email', 'beto@curaduria.test')->sole()->getRoleNames()->sort()->values()->all())
        ->toBe([EmployeeRole::Architect->value, EmployeeRole::Manager->value]);
});

test('asks for the name when it is not given', function () {
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test'])
        ->expectsQuestion('Nombre completo', 'Ana Pérez')
        ->assertSuccessful();

    expect(Employee::query()->where('email', 'ana@curaduria.test')->sole()->name)->toBe('Ana Pérez');
});

test('an unknown role is refused with the list of valid ones, and nothing is created', function () {
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test', '--name' => 'Ana Pérez', '--role' => ['jefe']])
        ->expectsOutputToContain('El rol "jefe" no existe. Roles: '.implode(', ', array_map(fn (EmployeeRole $role) => $role->value, EmployeeRole::cases())))
        ->assertFailed();

    expect(Employee::query()->count())->toBe(0);
});

test('refuses a duplicated email, a bad name or an unknown curaduría', function () {
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ana@curaduria.test', '--name' => 'Ana Pérez'])->assertSuccessful();

    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'ANA@curaduria.test', '--name' => 'Otra Ana'])->assertFailed();
    $this->artisan('employees:create', ['subdomain' => $this->subdomain, 'email' => 'otra@curaduria.test', '--name' => 'Al'])->assertFailed();
    $this->artisan('employees:create', ['subdomain' => 'no-existe', 'email' => 'ana@curaduria.test', '--name' => 'Ana Pérez'])
        ->expectsOutputToContain('No existe una curaduría')
        ->assertFailed();

    expect(Employee::query()->count())->toBe(1);
});
