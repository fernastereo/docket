<?php

declare(strict_types=1);

use App\Platform\Actions\CreatePlatformUser;
use App\Platform\Data\CreatePlatformUserData;
use App\Platform\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

const VALID_PASSWORD = 'plataforma-prueba-9f3k-Zq72';

// El chequeo contra brechas (HIBP) consulta una API externa: en los tests se
// simula que la contraseña NO figura (cuerpo vacío). Un test aparte cambia
// `$this->hibpBody` para simular que sí. Un segundo Http::fake() no sirve: gana
// el primer stub que coincide.
beforeEach(function () {
    $this->hibpBody = '';
    Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response($this->hibpBody, 200)]);
});

function createPlatformUser(array $overrides = []): PlatformUser
{
    $data = array_merge([
        'name' => 'Soporte Uno',
        'email' => 'soporte@curaduria.app',
        'password' => VALID_PASSWORD,
    ], $overrides);

    return app(CreatePlatformUser::class)->handle(new CreatePlatformUserData(...$data));
}

test('creates a platform user with an Argon2id hash and a uuid', function () {
    $user = createPlatformUser();

    expect($user->id)->toBeString()->toHaveLength(36)
        ->and($user->password)->not->toBe(VALID_PASSWORD)
        ->and($user->password)->toStartWith('$argon2id$')
        ->and(Hash::check(VALID_PASSWORD, $user->password))->toBeTrue();
});

test('the email is stored lowercased and trimmed', function () {
    $user = createPlatformUser(['email' => '  Soporte@Curaduria.APP ']);

    expect($user->fresh()->email)->toBe('soporte@curaduria.app');
});

test('rejects a duplicate email regardless of case', function () {
    createPlatformUser();

    createPlatformUser(['email' => 'SOPORTE@curaduria.app']);
})->throws(ValidationException::class);

test('rejects a password shorter than 12 characters', function () {
    createPlatformUser(['password' => 'corta-123']);
})->throws(ValidationException::class);

test('accepts a long passphrase without truncating it', function () {
    $long = str_repeat('frase-larga-', 10); // 120 caracteres

    $user = createPlatformUser(['password' => $long]);

    expect(Hash::check($long, $user->password))->toBeTrue();
});

test('rejects a password found in known breaches', function () {
    // Formato de la API de HIBP: "SUFIJO-DE-HASH:conteo" por línea. Se responde con
    // el sufijo real del SHA-1 de la contraseña para que cuente como comprometida.
    $sha1 = strtoupper(sha1(VALID_PASSWORD));
    $this->hibpBody = substr($sha1, 5).':9000';

    createPlatformUser();
})->throws(ValidationException::class);

test('rejects a malformed email', function () {
    createPlatformUser(['email' => 'no-es-un-email']);
})->throws(ValidationException::class);

test('the password never appears when the model is serialized', function () {
    $user = createPlatformUser();

    expect($user->toArray())->not->toHaveKey('password');
});
