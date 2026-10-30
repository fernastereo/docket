<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->subdomain = 'iv'.strtolower(Str::random(6));
    $this->tenant->domains()->create(['domain' => TenantSubdomain::hostFor($this->subdomain)]);

    app(CreateEmployee::class)->handle(new CreateEmployeeData('Ana Pérez', 'ana@curaduria.test', [EmployeeRole::Admin]));
});

afterEach(fn () => $this->tenant->domains()->delete());

test('sends the invitation to an employee of that curaduría', function () {
    $this->artisan('employees:invite', ['subdomain' => $this->subdomain, 'email' => 'ANA@curaduria.test'])
        ->expectsOutputToContain('Invitación enviada')
        ->assertSuccessful();

    Mail::assertQueued(EmployeeInvitationMail::class, fn (EmployeeInvitationMail $mail) => $mail->hasTo('ana@curaduria.test'));
});

test('fails for an unknown curaduría or employee and sends nothing', function () {
    $this->artisan('employees:invite', ['subdomain' => 'no-existe', 'email' => 'ana@curaduria.test'])
        ->expectsOutputToContain('No existe una curaduría')
        ->assertFailed();

    $this->artisan('employees:invite', ['subdomain' => $this->subdomain, 'email' => 'nadie@curaduria.test'])
        ->expectsOutputToContain('No existe un empleado')
        ->assertFailed();

    Mail::assertNothingQueued();
});
