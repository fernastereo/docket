<?php

declare(strict_types=1);

use App\Platform\Models\PlatformUser;
use Database\Factories\PlatformUserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// Mismo escenario que PlatformLoginTest: SPA de mismo origen con cookie de sesión.
function mfaUrl(string $path): string
{
    return 'https://'.config('tenancy.central_domains')[0].'/api/platform'.$path;
}

function mfaSpa(): array
{
    $host = config('tenancy.central_domains')[0];
    config(['sanctum.stateful' => [$host]]);

    return ['Origin' => "https://{$host}", 'Referer' => "https://{$host}/"];
}

function loginWith(PlatformUser $user): void
{
    test()->withHeaders(mfaSpa())
        ->postJson(mfaUrl('/login'), ['email' => $user->email, 'password' => PlatformUserFactory::PASSWORD])
        ->assertOk();
}

function currentCode(?string $secret = null): string
{
    return app(Google2FA::class)->getCurrentOtp($secret ?? PlatformUserFactory::MFA_SECRET);
}

beforeEach(fn () => RateLimiter::clear('platform-mfa|'));

test('a password-only session cannot reach the panel and is told which step is missing', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);

    $this->getJson(mfaUrl('/tenants'))
        ->assertForbidden()
        ->assertJsonPath('mfa', 'setup_required');

    $enrolled = PlatformUser::factory()->withMfa()->create();
    $this->postJson(mfaUrl('/logout'))->assertNoContent();
    loginWith($enrolled);

    $this->getJson(mfaUrl('/tenants'))
        ->assertForbidden()
        ->assertJsonPath('mfa', 'verification_required');
});

test('me reports the state of the second factor', function () {
    loginWith(PlatformUser::factory()->withMfa()->create());

    $this->getJson(mfaUrl('/me'))
        ->assertOk()
        ->assertJsonPath('data.mfa_enabled', true)
        ->assertJsonPath('data.mfa_verified', false);
});

test('setup returns a QR and a manual key, stores the secret encrypted and does not enable MFA yet', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);

    $response = $this->postJson(mfaUrl('/mfa/setup'))->assertOk();

    $secret = $response->json('data.secret');
    expect($response->json('data.uri'))->toStartWith('otpauth://totp/')->toContain($secret)
        ->and($response->json('data.qr'))->toStartWith('data:image/svg+xml;base64,');

    $user->refresh();
    expect($user->mfa_secret)->toBe($secret)
        ->and($user->hasMfaEnabled())->toBeFalse()
        ->and(DB::table('platform_users')->where('id', $user->id)->value('mfa_secret'))->not->toContain($secret);
});

test('confirming with a valid code enables MFA, returns the recovery codes once and unlocks the panel', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);
    $secret = $this->postJson(mfaUrl('/mfa/setup'))->json('data.secret');

    $response = $this->postJson(mfaUrl('/mfa/confirm'), ['code' => currentCode($secret)])->assertOk();

    expect($response->json('data.recovery_codes'))->toHaveCount(8)
        ->and($response->json('data.user.mfa_enabled'))->toBeTrue();
    expect($user->refresh()->hasMfaEnabled())->toBeTrue();
    $this->getJson(mfaUrl('/tenants'))->assertOk();
});

test('recovery codes are stored hashed, never in clear', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);
    $secret = $this->postJson(mfaUrl('/mfa/setup'))->json('data.secret');
    $codes = $this->postJson(mfaUrl('/mfa/confirm'), ['code' => currentCode($secret)])->json('data.recovery_codes');

    $stored = DB::table('platform_users')->where('id', $user->id)->value('mfa_recovery_codes');

    foreach ($codes as $code) {
        expect($stored)->not->toContain($code)->not->toContain(str_replace('-', '', $code));
    }
});

test('confirming with a wrong code does not enable MFA', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);
    $this->postJson(mfaUrl('/mfa/setup'))->assertOk();

    $this->postJson(mfaUrl('/mfa/confirm'), ['code' => '000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect($user->refresh()->hasMfaEnabled())->toBeFalse();
    $this->getJson(mfaUrl('/tenants'))->assertForbidden();
});

test('confirming without starting the setup is rejected', function () {
    loginWith(PlatformUser::factory()->create());

    $this->postJson(mfaUrl('/mfa/confirm'), ['code' => '123456'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mfa');
});

test('setup is refused once MFA is enabled', function () {
    loginWith(PlatformUser::factory()->withMfa()->create());

    $this->postJson(mfaUrl('/mfa/setup'))->assertUnprocessable()->assertJsonValidationErrors('mfa');
});

test('a valid TOTP code completes the login and unlocks the panel', function () {
    loginWith(PlatformUser::factory()->withMfa()->create());

    $this->postJson(mfaUrl('/mfa/verify'), ['code' => currentCode()])
        ->assertOk()
        ->assertJsonPath('data.mfa_verified', true);

    $this->getJson(mfaUrl('/tenants'))->assertOk();
});

test('a wrong code keeps the panel locked', function () {
    loginWith(PlatformUser::factory()->withMfa()->create());

    $this->postJson(mfaUrl('/mfa/verify'), ['code' => '000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->getJson(mfaUrl('/tenants'))->assertForbidden();
});

test('a TOTP code cannot be used twice', function () {
    $user = PlatformUser::factory()->withMfa()->create();
    loginWith($user);
    $code = currentCode();
    $this->postJson(mfaUrl('/mfa/verify'), ['code' => $code])->assertOk();

    // Nueva sesión (otro login) con el mismo código, todavía dentro de su ventana.
    $this->postJson(mfaUrl('/logout'))->assertNoContent();
    loginWith($user);

    $this->postJson(mfaUrl('/mfa/verify'), ['code' => $code])->assertUnprocessable();
});

test('a recovery code works once and is then consumed', function () {
    $user = PlatformUser::factory()->create();
    loginWith($user);
    $secret = $this->postJson(mfaUrl('/mfa/setup'))->json('data.secret');
    $codes = $this->postJson(mfaUrl('/mfa/confirm'), ['code' => currentCode($secret)])->json('data.recovery_codes');
    $this->postJson(mfaUrl('/logout'))->assertNoContent();

    loginWith($user);
    $this->postJson(mfaUrl('/mfa/verify'), ['code' => $codes[0]])->assertOk();
    expect($user->refresh()->mfa_recovery_codes)->toHaveCount(7);

    $this->postJson(mfaUrl('/logout'))->assertNoContent();
    loginWith($user);
    $this->postJson(mfaUrl('/mfa/verify'), ['code' => $codes[0]])->assertUnprocessable();
    // Tolerante a minúsculas y sin guion: se teclea a mano.
    $this->postJson(mfaUrl('/mfa/verify'), ['code' => strtolower(str_replace('-', '', $codes[1]))])->assertOk();
});

test('the second step locks after too many failures, even with the right code afterwards', function () {
    $user = PlatformUser::factory()->withMfa()->create();
    loginWith($user);

    foreach (range(1, (int) config('mfa.max_attempts')) as $ignored) {
        $this->postJson(mfaUrl('/mfa/verify'), ['code' => '000000'])->assertUnprocessable();
    }

    $this->postJson(mfaUrl('/mfa/verify'), ['code' => currentCode()])->assertStatus(429);
    $this->getJson(mfaUrl('/tenants'))->assertForbidden();
});

test('a new login asks for the second factor again', function () {
    $user = PlatformUser::factory()->withMfa()->create();
    loginWith($user);
    $this->postJson(mfaUrl('/mfa/verify'), ['code' => currentCode()])->assertOk();
    $this->getJson(mfaUrl('/tenants'))->assertOk();

    $this->postJson(mfaUrl('/logout'))->assertNoContent();
    loginWith($user);

    $this->getJson(mfaUrl('/tenants'))->assertForbidden();
});

test('the session id is rotated when the second factor is verified', function () {
    loginWith(PlatformUser::factory()->withMfa()->create());
    $before = Session::getId();

    $this->postJson(mfaUrl('/mfa/verify'), ['code' => currentCode()])->assertOk();

    expect(Session::getId())->not->toBe($before);
});

test('the MFA endpoints require a platform session', function () {
    foreach (['/mfa/setup', '/mfa/confirm', '/mfa/verify'] as $path) {
        $this->withHeaders(mfaSpa())->postJson(mfaUrl($path), ['code' => '123456'])->assertUnauthorized();
    }
});

test('the user resource and the model never expose the secret or the recovery codes', function () {
    $user = PlatformUser::factory()->withMfa()->create();
    loginWith($user);

    expect(json_encode($this->getJson(mfaUrl('/me'))->json()))->not->toContain(PlatformUserFactory::MFA_SECRET)
        ->and($user->toArray())->not->toHaveKeys(['mfa_secret', 'mfa_recovery_codes']);
});

test('the authenticator entry carries the issuer, so environments can be told apart', function () {
    config(['mfa.issuer' => 'CuraduriAPP (staging)']);
    loginWith(PlatformUser::factory()->create());

    $uri = $this->postJson(mfaUrl('/mfa/setup'))->json('data.uri');

    expect(rawurldecode($uri))->toContain('CuraduriAPP (staging)');
});
