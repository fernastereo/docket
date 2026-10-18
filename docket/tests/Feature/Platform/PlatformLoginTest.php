<?php

declare(strict_types=1);

use App\Platform\Models\PlatformUser;
use Database\Factories\PlatformUserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// El login solo existe en los dominios centrales (config/tenancy.php). Sanctum
// da sesión únicamente a peticiones "stateful" (Origin/Referer de un dominio
// de SANCTUM_STATEFUL_DOMAINS): se imita al navegador de la SPA con esos headers.
function centralHost(): string
{
    return config('tenancy.central_domains')[0];
}

function spa(): array
{
    $host = centralHost();
    config(['sanctum.stateful' => [$host]]);

    return ['Origin' => "https://{$host}", 'Referer' => "https://{$host}/"];
}

function loginPlatform(array $credentials): TestResponse
{
    return test()->withHeaders(spa())
        ->postJson('https://'.centralHost().'/api/platform/login', $credentials);
}

function platformUser(array $state = []): PlatformUser
{
    return PlatformUser::factory()->create(array_merge(['email' => 'soporte@curaduria.app'], $state));
}

function validCredentials(array $overrides = []): array
{
    return array_merge(['email' => 'soporte@curaduria.app', 'password' => PlatformUserFactory::PASSWORD], $overrides);
}

beforeEach(fn () => RateLimiter::clear('platform-login|soporte@curaduria.app|127.0.0.1'));

test('a platform user logs in and receives its own data, never the password', function () {
    platformUser();

    $response = loginPlatform(validCredentials());

    $response->assertOk()
        ->assertJsonPath('data.email', 'soporte@curaduria.app')
        ->assertJsonMissingPath('data.password');
    $this->assertAuthenticated('platform');
});

test('login records the last login time', function () {
    $user = platformUser();

    loginPlatform(validCredentials())->assertOk();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('the email is matched case-insensitively', function () {
    platformUser();

    loginPlatform(validCredentials(['email' => 'SOPORTE@Curaduria.app']))->assertOk();
});

test('a wrong password, an unknown email and a disabled account all fail the same way', function () {
    platformUser();
    platformUser(['email' => 'baja@curaduria.app', 'disabled_at' => now()]);

    $wrongPassword = loginPlatform(validCredentials(['password' => 'otra-contraseña-cualquiera']));
    $unknownEmail = loginPlatform(validCredentials(['email' => 'nadie@curaduria.app']));
    $disabled = loginPlatform(validCredentials(['email' => 'baja@curaduria.app']));

    foreach ([$wrongPassword, $unknownEmail, $disabled] as $response) {
        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }
    expect($unknownEmail->json('errors.email'))->toBe($wrongPassword->json('errors.email'))
        ->and($disabled->json('errors.email'))->toBe($wrongPassword->json('errors.email'));
    $this->assertGuest('platform');
});

test('a disabled account cannot log in even with the right password', function () {
    platformUser(['disabled_at' => now()]);

    loginPlatform(validCredentials())->assertStatus(422);
    $this->assertGuest('platform');
});

test('login is blocked after 5 failed attempts and says when to retry', function () {
    platformUser();

    foreach (range(1, 5) as $_) {
        loginPlatform(validCredentials(['password' => 'incorrecta-incorrecta']))->assertStatus(422);
    }

    // El 6.º intento queda bloqueado, incluso con la contraseña correcta.
    loginPlatform(validCredentials())
        ->assertStatus(429)
        ->assertHeader('Retry-After');
    $this->assertGuest('platform');
});

test('a successful login resets the failed-attempts counter', function () {
    platformUser();

    foreach (range(1, 4) as $_) {
        loginPlatform(validCredentials(['password' => 'incorrecta-incorrecta']));
    }
    loginPlatform(validCredentials())->assertOk();
    auth('platform')->logout();

    foreach (range(1, 4) as $_) {
        loginPlatform(validCredentials(['password' => 'incorrecta-incorrecta']))->assertStatus(422);
    }
});

test('the session id is rotated on login', function () {
    platformUser();

    $this->withHeaders(spa())->getJson('https://'.centralHost().'/up');
    $before = session()->getId();

    loginPlatform(validCredentials())->assertOk();

    expect(session()->getId())->not->toBe($before);
});

test('login without a stateful origin is refused, not silently ignored', function () {
    platformUser();

    $this->postJson('https://'.centralHost().'/api/platform/login', validCredentials())
        ->assertStatus(400);
});

test('missing fields are validation errors', function () {
    loginPlatform([])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
});

test('me requires an authenticated platform user', function () {
    $this->withHeaders(spa())->getJson('https://'.centralHost().'/api/platform/me')->assertUnauthorized();
});

test('me returns the logged in user', function () {
    platformUser();
    loginPlatform(validCredentials())->assertOk();

    $this->getJson('https://'.centralHost().'/api/platform/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'soporte@curaduria.app');
});

test('logout ends the session', function () {
    platformUser();
    loginPlatform(validCredentials())->assertOk();

    $this->postJson('https://'.centralHost().'/api/platform/logout')->assertNoContent();

    $this->assertGuest('platform');
});

test('the session expires after the idle limit', function () {
    platformUser();
    loginPlatform(validCredentials())->assertOk();
    $this->travel((int) config('platform.session.idle_minutes') + 1)->minutes();

    $this->getJson('https://'.centralHost().'/api/platform/me')->assertUnauthorized();
    $this->assertGuest('platform');
});

test('activity keeps the session alive within the idle limit', function () {
    platformUser();
    loginPlatform(validCredentials())->assertOk();

    foreach (range(1, 3) as $_) {
        $this->travel(10)->minutes();
        $this->getJson('https://'.centralHost().'/api/platform/me')->assertOk();
    }
});

test('the session expires at the absolute limit even with constant activity', function () {
    platformUser();
    loginPlatform(validCredentials())->assertOk();

    $steps = (int) config('platform.session.absolute_hours') * 60 / 10;
    foreach (range(1, $steps) as $_) {
        $this->travel(10)->minutes();
        $this->getJson('https://'.centralHost().'/api/platform/me');
    }
    $this->travel(10)->minutes();

    $this->getJson('https://'.centralHost().'/api/platform/me')->assertUnauthorized();
});

test('a session without the login stamp is discarded', function () {
    $user = platformUser();

    $this->actingAs($user, 'platform')->withHeaders(spa())
        ->getJson('https://'.centralHost().'/api/platform/me')
        ->assertUnauthorized();
});

test('platform routes do not exist on a tenant subdomain', function () {
    $this->withHeaders(['Origin' => 'https://curaduria-x.docket.test'])
        ->postJson('https://curaduria-x.docket.test/api/platform/login', validCredentials())
        ->assertNotFound();
});
