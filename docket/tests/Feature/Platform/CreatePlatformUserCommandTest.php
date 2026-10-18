<?php

declare(strict_types=1);

use App\Platform\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(fn () => Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]));

test('creates a platform user asking for the password interactively', function () {
    $this->artisan('platform:create-user', ['name' => 'Soporte Uno', 'email' => 'soporte@curaduria.app'])
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'plataforma-prueba-9f3k-Zq72')
        ->expectsQuestion('Repite la contraseña', 'plataforma-prueba-9f3k-Zq72')
        ->expectsOutputToContain('Usuario de plataforma creado')
        ->assertSuccessful();

    expect(PlatformUser::where('email', 'soporte@curaduria.app')->exists())->toBeTrue();
});

test('fails when the two passwords do not match and creates nothing', function () {
    $this->artisan('platform:create-user', ['name' => 'Soporte Uno', 'email' => 'soporte@curaduria.app'])
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'plataforma-prueba-9f3k-Zq72')
        ->expectsQuestion('Repite la contraseña', 'otra-distinta-1234567')
        ->expectsOutputToContain('no coinciden')
        ->assertFailed();

    expect(PlatformUser::count())->toBe(0);
});

test('reports validation errors such as a short password', function () {
    $this->artisan('platform:create-user', ['name' => 'Soporte Uno', 'email' => 'soporte@curaduria.app'])
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
        ->expectsQuestion('Repite la contraseña', 'corta')
        ->assertFailed();

    expect(PlatformUser::count())->toBe(0);
});
