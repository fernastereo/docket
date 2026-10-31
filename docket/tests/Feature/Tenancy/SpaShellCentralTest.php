<?php

declare(strict_types=1);

// El shell de la SPA en el dominio central: sin curaduría, es el panel de
// plataforma. (La contraparte, con curaduría, está en Identity/SpaShellTest.)
test('the central domain tells the SPA it is the platform panel', function () {
    $this->withoutVite();

    $this->get('https://'.config('tenancy.central_domains')[0].'/')
        ->assertOk()
        ->assertSee('data-context="platform"', false)
        ->assertDontSee('data-tenant-name', false);
});
