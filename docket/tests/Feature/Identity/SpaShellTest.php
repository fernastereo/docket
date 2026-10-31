<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Concerns\UsesTenantDatabase;

uses(UsesTenantDatabase::class);

// El mismo shell de la SPA se sirve en el dominio central y en cada curaduría;
// el Blade le dice a la SPA en cuál está.
beforeEach(function () {
    $this->withoutVite();
    $this->host = 'shell-'.strtolower(Str::random(6)).'.'.config('platform.tenant_base_domain');
    $this->tenant->domains()->create(['domain' => $this->host]);
});

afterEach(fn () => $this->tenant->domains()->delete());

test('a curaduría subdomain tells the SPA it is a tenant and which one', function () {
    $this->get("https://{$this->host}/")
        ->assertOk()
        ->assertSee('data-context="tenant"', false)
        ->assertSee('data-tenant-name="'.e($this->tenant->name).'"', false);
});
