<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SeedEmployeeRoles;
use App\Domain\Identity\Enums\EmployeeRole;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

test('the tenant gets every employee role when it is provisioned', function () {
    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(EmployeeRole::cases())->map->value->sort()->values()->all());
});

test('the roles belong to the employee guard', function () {
    expect(Role::query()->pluck('guard_name')->unique()->all())->toBe(['employee']);
});

test('seeding the roles again does not duplicate them', function () {
    app(SeedEmployeeRoles::class)->handle();
    app(SeedEmployeeRoles::class)->handle();

    expect(Role::query()->count())->toBe(count(EmployeeRole::cases()));
});

test('every role has a label and only curator and admin require MFA', function () {
    foreach (EmployeeRole::cases() as $role) {
        expect($role->label())->not->toBe('');
    }

    $needMfa = collect(EmployeeRole::cases())->filter->requiresMfa()->map->value->values()->all();

    expect($needMfa)->toBe(['curator', 'admin']);
});

test('architects, engineers and lawyers each have an assistant role, plus archive, accounting and management', function () {
    $values = collect(EmployeeRole::cases())->map->value;

    expect($values)->toContain(
        'architect_assistant', 'engineer_assistant', 'lawyer_assistant', 'records', 'accounting', 'manager',
    );
});
