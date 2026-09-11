<?php

declare(strict_types=1);
use App\Providers\TenancyServiceProvider;

test('central domains are driven by TENANCY_CENTRAL_DOMAINS, not hardcoded', function () {
    config(['tenancy.central_domains' => ['docket.test', 'central.docket.test']]);

    expect(config('tenancy.central_domains'))
        ->toContain('docket.test')
        ->toContain('central.docket.test');
});

test('the tenancy service provider is registered', function () {
    expect(collect(app()->getLoadedProviders())->keys())
        ->toContain(TenancyServiceProvider::class);
});
