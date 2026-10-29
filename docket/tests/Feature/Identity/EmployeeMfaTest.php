<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Models\Employee;
use App\Support\Mfa\BeginMfaSetup;
use App\Support\Mfa\ConfirmMfa;
use App\Support\Mfa\Mfa;
use App\Support\Mfa\VerifyMfa;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

function mfaEmployee(): Employee
{
    return app(CreateEmployee::class)->handle(new CreateEmployeeData(
        name: 'Ana Pérez',
        email: 'ana@curaduria.test',
        roles: [EmployeeRole::Admin],
    ));
}

function enrolledEmployee(): Employee
{
    $employee = mfaEmployee();
    $setup = app(BeginMfaSetup::class)->handle($employee);
    app(ConfirmMfa::class)->handle($employee, app(Google2FA::class)->getCurrentOtp($setup['secret']));

    return $employee->refresh();
}

test('an employee activates the second factor with the same flow as platform users', function () {
    $employee = mfaEmployee();
    expect($employee->hasMfaEnabled())->toBeFalse();

    $setup = app(BeginMfaSetup::class)->handle($employee);

    expect($setup['uri'])->toContain(rawurlencode('ana@curaduria.test'))
        ->and($setup['qr'])->toStartWith('data:image/svg+xml;base64,')
        // Pendiente de confirmar: todavía no cuenta como activa.
        ->and($employee->refresh()->hasMfaEnabled())->toBeFalse();

    $codes = app(ConfirmMfa::class)->handle($employee, app(Google2FA::class)->getCurrentOtp($setup['secret']));

    expect($codes)->toHaveCount(8)
        ->and($employee->refresh()->hasMfaEnabled())->toBeTrue();
});

test('the secret and the recovery codes are stored protected and never serialized', function () {
    $employee = enrolledEmployee();
    $raw = DB::table('employees')->where('id', $employee->id)->first();

    expect($raw->mfa_secret)->not->toBe($employee->mfa_secret)
        ->and($employee->toArray())->not->toHaveKeys(['mfa_secret', 'mfa_recovery_codes', 'password']);
});

test('a wrong code is rejected and locks the account after too many attempts', function () {
    $employee = enrolledEmployee();

    foreach (range(1, (int) config('mfa.max_attempts')) as $ignored) {
        expect(fn () => app(VerifyMfa::class)->handle($employee, '000000'))->toThrow(ValidationException::class);
    }

    $valid = app(Google2FA::class)->getCurrentOtp($employee->mfa_secret);
    expect(fn () => app(VerifyMfa::class)->handle($employee, $valid))->toThrow(ThrottleRequestsException::class);
});

test('a TOTP code cannot be used twice and a recovery code works once', function () {
    $employee = mfaEmployee();
    $setup = app(BeginMfaSetup::class)->handle($employee);
    $secret = $setup['secret'];
    $recovery = app(ConfirmMfa::class)->handle($employee, app(Google2FA::class)->getCurrentOtp($secret));

    // El código con el que se confirmó ya está gastado.
    expect(fn () => app(VerifyMfa::class)->handle($employee->refresh(), app(Google2FA::class)->getCurrentOtp($secret)))
        ->toThrow(ValidationException::class);

    app(VerifyMfa::class)->handle($employee->refresh(), $recovery[0]);

    expect(fn () => app(VerifyMfa::class)->handle($employee->refresh(), $recovery[0]))
        ->toThrow(ValidationException::class)
        ->and($employee->refresh()->mfa_recovery_codes)->toHaveCount(7);
});

test('the throttle key is per account and separate from platform users', function () {
    $employee = mfaEmployee();

    expect($employee->mfaThrottleKey())->toBe('employee-mfa|'.$employee->id)
        ->and(app(Mfa::class))->toBeInstanceOf(Mfa::class);
});

test('only curator and admin are required to have MFA', function () {
    $required = collect(EmployeeRole::cases())->filter->requiresMfa()->map->value->all();

    expect($required)->toEqualCanonicalizing([EmployeeRole::Curator->value, EmployeeRole::Admin->value]);
});
