<?php

declare(strict_types=1);

// Configuración de plataforma (base central). Lo de stancl/tenancy sigue en
// config/tenancy.php; esto es lo propio de CuraduriAPP.

return [
    // Dominio bajo el cual cuelgan las curadurías: <subdominio>.<este valor>.
    // local: docket.test · dev: staging.curaduria.app · prod: curaduria.app
    'tenant_base_domain' => env('PLATFORM_TENANT_BASE_DOMAIN', 'docket.test'),

    // Subdominios que ninguna curaduría puede tomar (infraestructura, correo,
    // suplantación de la propia plataforma).
    'reserved_subdomains' => [
        'www', 'api', 'app', 'admin', 'panel', 'plataforma', 'platform',
        'central', 'staging', 'dev', 'test', 'demo', 'prod', 'status',
        'mail', 'smtp', 'imap', 'ftp', 'cdn', 'static', 'assets', 'files',
        'storage', 'docs', 'help', 'soporte', 'support', 'blog', 'landing',
        'login', 'auth', 'portal', 'verificar', 'verify',
    ],
];
