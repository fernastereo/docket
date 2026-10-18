<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las bases físicas de tenants que crean los tests llevan este prefijo, no el
     * de desarrollo (`tenant`): se reconocen a simple vista y `make test-clean`
     * puede borrarlas sin riesgo de tocar las de una curaduría real.
     */
    public const TENANT_DATABASE_PREFIX = 'tenanttest_';

    /**
     * Se ejecuta ANTES de los traits de base de datos (RefreshDatabase,
     * DatabaseMigrations), que hacen migrate:fresh. Es la última defensa
     * (phpunit.xml ya fuerza docket_test): si por cualquier motivo la conexión
     * apunta a otra base, aborta antes de tocar nada.
     */
    protected function setUpTraits()
    {
        $default = config('database.default');
        $database = (string) config("database.connections.{$default}.database");

        if ($default !== 'pgsql' || ! str_ends_with($database, '_test')) {
            throw new RuntimeException(sprintf(
                'Los tests exigen PostgreSQL y una base terminada en "_test" (docket_test). Conexión actual: %s / %s. Abortado antes de migrar.',
                $default,
                $database,
            ));
        }

        config(['tenancy.database.prefix' => self::TENANT_DATABASE_PREFIX]);

        return parent::setUpTraits();
    }
}
