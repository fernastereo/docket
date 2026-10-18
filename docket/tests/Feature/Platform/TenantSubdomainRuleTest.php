<?php

declare(strict_types=1);

use App\Platform\Models\Tenant;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Stancl\Tenancy\Events\TenantCreated;

uses(RefreshDatabase::class);

function subdomainErrors(mixed $value): array
{
    return Validator::make(['subdomain' => $value], ['subdomain' => [new TenantSubdomain]])
        ->errors()
        ->get('subdomain');
}

test('accepts a well-formed subdomain', function (string $subdomain) {
    expect(subdomainErrors($subdomain))->toBeEmpty();
})->with(['cu1bogota', 'curaduria-2-cali', 'abc', 'a1b', str_repeat('a', 40)]);

test('rejects malformed subdomains', function (mixed $subdomain) {
    expect(subdomainErrors($subdomain))->not->toBeEmpty();
})->with([
    'too short' => 'ab',
    'too long' => str_repeat('a', 41),
    'uppercase' => 'CuBogota',
    'underscore' => 'cu_bogota',
    'dot' => 'cu.bogota',
    'space' => 'cu bogota',
    'accent' => 'curaduría',
    'leading hyphen' => '-cubogota',
    'trailing hyphen' => 'cubogota-',
    'double hyphen (punycode)' => 'xn--cubogota',
    'not a string' => 12345,
]);

test('rejects reserved subdomains', function (string $subdomain) {
    expect(subdomainErrors($subdomain))->toContain('Ese subdominio está reservado por la plataforma.');
})->with(['www', 'admin', 'api', 'staging', 'login']);

test('rejects a subdomain already used by another tenant', function () {
    config(['platform.tenant_base_domain' => 'docket.test']);
    Event::fake([TenantCreated::class]);

    $tenant = Tenant::create(['name' => 'Curaduría Existente']);
    $tenant->domains()->create(['domain' => 'cu1bogota.docket.test']);

    expect(subdomainErrors('cu1bogota'))->toContain('Ese subdominio ya está en uso por otra curaduría.')
        ->and(subdomainErrors('cu2bogota'))->toBeEmpty();
});

test('the full host is built from the configured base domain', function () {
    config(['platform.tenant_base_domain' => 'curaduria.app']);

    expect(TenantSubdomain::hostFor('cu1bogota'))->toBe('cu1bogota.curaduria.app');
});
