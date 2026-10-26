<?php

declare(strict_types=1);

use App\Platform\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('platform:reset-mfa clears the second factor so it must be enrolled again', function () {
    $user = PlatformUser::factory()->withMfa()->create(['email' => 'soporte@curaduria.app']);
    $user->forceFill(['mfa_recovery_codes' => ['x'], 'mfa_last_used_timestamp' => 123])->save();

    $this->artisan('platform:reset-mfa', ['email' => 'Soporte@Curaduria.app'])->assertSuccessful();

    $user->refresh();
    expect($user->hasMfaEnabled())->toBeFalse()
        ->and($user->mfa_secret)->toBeNull()
        ->and($user->mfa_recovery_codes)->toBeNull()
        ->and($user->mfa_last_used_timestamp)->toBeNull();
});

test('platform:reset-mfa fails for an unknown email', function () {
    $this->artisan('platform:reset-mfa', ['email' => 'nadie@curaduria.app'])->assertFailed();
});
