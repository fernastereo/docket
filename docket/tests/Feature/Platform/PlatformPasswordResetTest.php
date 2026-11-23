<?php

declare(strict_types=1);

use App\Platform\Actions\SetPlatformUserPassword;
use App\Platform\Mail\PlatformPasswordResetMail;
use App\Platform\Models\PlatformPasswordReset;
use App\Platform\Models\PlatformUser;
use Database\Factories\PlatformUserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

const RESET_NEW_PASSWORD = 'contraseña-nueva-plataforma-2026';

beforeEach(function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    Mail::fake();
    $this->withoutDefer(); // el trabajo posterior a la respuesta corre dentro del test

    $this->central = config('tenancy.central_domains')[0];
    config(['sanctum.stateful' => [$this->central], 'platform.tenant_url_port' => null]);
});

function resetUser(array $state = []): PlatformUser
{
    return PlatformUser::factory()->create(array_merge(['name' => 'Fernando Cueto', 'email' => 'soporte@curaduria.app'], $state));
}

function resetApi(string $method, string $path, array $data = []): TestResponse
{
    $host = test()->central;

    return test()->withHeaders(['Origin' => "https://{$host}", 'Referer' => "https://{$host}/"])
        ->json($method, "https://{$host}/api/platform{$path}", $data);
}

function forgotPlatform(string $email = 'soporte@curaduria.app'): TestResponse
{
    return resetApi('POST', '/forgot-password', ['email' => $email]);
}

/** Pide la recuperación y devuelve el token en claro, tal como lo recibiría la persona en el correo. */
function platformResetToken(string $email = 'soporte@curaduria.app'): string
{
    forgotPlatform($email)->assertStatus(202);

    $url = '';
    Mail::assertQueued(PlatformPasswordResetMail::class, function (PlatformPasswordResetMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    return Str::afterLast($url, '/reset-password/');
}

test('a platform user gets a recovery link to the panel by email', function () {
    resetUser();

    forgotPlatform()->assertStatus(202);

    Mail::assertQueued(PlatformPasswordResetMail::class, fn (PlatformPasswordResetMail $mail) => $mail->hasTo('soporte@curaduria.app')
        && str_starts_with($mail->url, "https://{$this->central}/platform/reset-password/")
        && $mail->validMinutes === 60);
    expect(PlatformPasswordReset::query()->sole()->token_hash)->toHaveLength(64);
});

test('the link carries the local port when one is configured', function () {
    resetUser();
    config(['platform.tenant_url_port' => 8443]);

    forgotPlatform()->assertStatus(202);

    Mail::assertQueued(PlatformPasswordResetMail::class, fn (PlatformPasswordResetMail $mail) => str_starts_with($mail->url, "https://{$this->central}:8443/platform/reset-password/"));
});

test('only the hash of the token is stored', function () {
    resetUser();

    $token = platformResetToken();

    $stored = PlatformPasswordReset::query()->sole();
    expect($stored->token_hash)->toBe(hash('sha256', $token))->not->toContain($token);
});

test('the answer is the same whether the account exists, is disabled or does not exist', function () {
    resetUser();
    resetUser(['email' => 'baja@curaduria.app'])->forceFill(['disabled_at' => now()])->save();

    $known = forgotPlatform('soporte@curaduria.app');
    $disabled = forgotPlatform('baja@curaduria.app');
    $unknown = forgotPlatform('nadie@curaduria.app');

    expect($known->status())->toBe(202)
        ->and($disabled->getContent())->toBe($known->getContent())
        ->and($unknown->getContent())->toBe($known->getContent())
        ->and($known->json('message'))->toBe('Si ese correo tiene una cuenta de plataforma, te enviamos un enlace para elegir una contraseña nueva.');
    Mail::assertQueued(PlatformPasswordResetMail::class, 1);
});

test('the email is matched ignoring case and surrounding spaces', function () {
    resetUser();

    forgotPlatform('  SOPORTE@Curaduria.APP ')->assertStatus(202);

    Mail::assertQueued(PlatformPasswordResetMail::class, fn (PlatformPasswordResetMail $mail) => $mail->hasTo('soporte@curaduria.app'));
});

test('an account gets at most three emails per window, and requests for unknown accounts count too', function () {
    resetUser();
    foreach (range(1, 5) as $_) {
        forgotPlatform()->assertStatus(202);
    }
    Mail::assertQueued(PlatformPasswordResetMail::class, 3);

    foreach (range(1, 3) as $_) {
        forgotPlatform('nadie@curaduria.app')->assertStatus(202);
    }
    resetUser(['email' => 'nadie@curaduria.app']);
    forgotPlatform('nadie@curaduria.app')->assertStatus(202);
    Mail::assertQueued(PlatformPasswordResetMail::class, 3); // no se envió ninguno más
});

test('a malformed or missing email is a validation error', function () {
    resetApi('POST', '/forgot-password', ['email' => 'no-es-un-correo'])->assertStatus(422)->assertJsonValidationErrors('email');
    resetApi('POST', '/forgot-password')->assertStatus(422)->assertJsonValidationErrors('email');
});

test('the recovery endpoints do not exist on a tenant subdomain', function () {
    $this->postJson('https://cuuno.docket.test/api/platform/forgot-password', ['email' => 'a@b.co'])->assertNotFound();
    $this->getJson('https://cuuno.docket.test/api/platform/reset-password/abc')->assertNotFound();
});

test('the link shows who is recovering their access', function () {
    resetUser();
    $token = platformResetToken();

    resetApi('GET', "/reset-password/{$token}")
        ->assertOk()
        ->assertExactJson(['data' => ['name' => 'Fernando Cueto', 'email' => 'soporte@curaduria.app']]);
});

test('an unknown, used or expired token all answer the same 404', function () {
    resetUser();
    $token = platformResetToken();

    $unknown = resetApi('GET', '/reset-password/'.Str::random(64));
    expect($unknown->status())->toBe(404);

    PlatformPasswordReset::query()->update(['expires_at' => now()->subMinute()]);
    $expired = resetApi('GET', "/reset-password/{$token}");

    PlatformPasswordReset::query()->update(['expires_at' => now()->addHour(), 'used_at' => now()]);
    $used = resetApi('POST', "/reset-password/{$token}", ['password' => RESET_NEW_PASSWORD, 'password_confirmation' => RESET_NEW_PASSWORD]);

    expect($expired->status())->toBe(404)
        ->and($used->status())->toBe(404)
        ->and($expired->json('message'))->toBe($unknown->json('message'));
});

test('choosing a new password spends the link, keeps the MFA and lets the user in with it', function () {
    $user = resetUser();
    $user->forceFill(['mfa_secret' => PlatformUserFactory::MFA_SECRET, 'mfa_confirmed_at' => now()])->save();
    $token = platformResetToken();

    resetApi('POST', "/reset-password/{$token}", ['password' => RESET_NEW_PASSWORD, 'password_confirmation' => RESET_NEW_PASSWORD])->assertNoContent();

    $user->refresh();
    expect(Hash::check(RESET_NEW_PASSWORD, $user->password))->toBeTrue()
        ->and($user->hasMfaEnabled())->toBeTrue()
        ->and($user->password_changed_at)->not->toBeNull();

    // El enlace no sirve dos veces, y la contraseña vieja ya no entra.
    resetApi('POST', "/reset-password/{$token}", ['password' => 'otra-contraseña-larga-2026', 'password_confirmation' => 'otra-contraseña-larga-2026'])->assertNotFound();
    resetApi('POST', '/login', ['email' => 'soporte@curaduria.app', 'password' => PlatformUserFactory::PASSWORD])->assertStatus(422);
    resetApi('POST', '/login', ['email' => 'soporte@curaduria.app', 'password' => RESET_NEW_PASSWORD])->assertOk();
});

test('the password policy answers in Spanish and a failed attempt keeps the link usable', function (string $password, string $confirmation, string $message) {
    resetUser();
    $token = platformResetToken();
    // Reemplaza el Http::fake del beforeEach (gana el primero registrado): aquí la
    // contraseña "filtrada" debe aparecer en la respuesta de HIBP.
    app()->forgetInstance(Factory::class);
    Http::clearResolvedInstances();
    $hash = strtoupper(sha1('contraseña-filtrada-larga'));
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($hash, 5).':9999', 200)]);

    $response = resetApi('POST', "/reset-password/{$token}", ['password' => $password, 'password_confirmation' => $confirmation])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');

    expect($response->json('errors.password.0'))->toBe($message)
        ->and(PlatformPasswordReset::findUsable($token))->not->toBeNull();
})->with([
    'too short' => ['corta-12', 'corta-12', 'La contraseña debe tener al menos 12 caracteres.'],
    'not confirmed' => ['una-frase-muy-larga-1', 'otra-frase-muy-larga-2', 'Las contraseñas no coinciden.'],
    'leaked' => ['contraseña-filtrada-larga', 'contraseña-filtrada-larga', 'Esa contraseña aparece en filtraciones de datos conocidas. Elige otra.'],
]);

test('a user disabled after asking for the link cannot use it', function () {
    $user = resetUser();
    $token = platformResetToken();
    $user->forceFill(['disabled_at' => now()])->save();

    resetApi('POST', "/reset-password/{$token}", ['password' => RESET_NEW_PASSWORD, 'password_confirmation' => RESET_NEW_PASSWORD])
        ->assertStatus(422)
        ->assertJsonValidationErrors('token');
    expect(Hash::check(PlatformUserFactory::PASSWORD, $user->refresh()->password))->toBeTrue();
});

test('changing the password closes the sessions opened before and not the ones opened after', function () {
    $user = resetUser();
    resetApi('POST', '/login', ['email' => 'soporte@curaduria.app', 'password' => PlatformUserFactory::PASSWORD])->assertOk();
    resetApi('GET', '/me')->assertOk();

    $this->travel(10)->seconds();
    app(SetPlatformUserPassword::class)->handle($user, RESET_NEW_PASSWORD);
    app('auth')->forgetGuards(); // en producción cada petición carga al usuario de nuevo

    resetApi('GET', '/me')->assertUnauthorized();

    resetApi('POST', '/login', ['email' => 'soporte@curaduria.app', 'password' => RESET_NEW_PASSWORD])->assertOk();
    resetApi('GET', '/me')->assertOk();
});

test('the recovery email renders with the name, the link and the expiry', function () {
    $html = (new PlatformPasswordResetMail('Fernando Cueto', 'https://docket.test/platform/reset-password/abc', 60))->render();

    expect($html)->toContain('Fernando Cueto')
        ->and($html)->toContain('https://docket.test/platform/reset-password/abc')
        ->and($html)->toContain('60 minutos');
});
