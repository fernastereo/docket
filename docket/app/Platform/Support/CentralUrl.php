<?php

declare(strict_types=1);

namespace App\Platform\Support;

/**
 * Enlaces al dominio central (el panel de plataforma) para los correos. Igual que
 * `Tenant::url()`: en local la app se sirve en un puerto distinto (config
 * `platform.tenant_url_port`); en dev y prod no hay puerto.
 */
final class CentralUrl
{
    public static function to(string $path = '/'): string
    {
        $host = (string) config('tenancy.central_domains.0');
        $port = config('platform.tenant_url_port');

        return 'https://'.$host.($port ? ':'.$port : '').'/'.ltrim($path, '/');
    }
}
