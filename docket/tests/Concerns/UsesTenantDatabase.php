<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;
use Throwable;

/**
 * Para tests de DOMINIO que corren dentro de la base de un tenant (expedientes,
 * expensas, numeración…). Uso: `uses(UsesTenantDatabase::class);` en el archivo.
 *
 * - Un único tenant de pruebas por proceso (provisionado la primera vez con
 *   ProvisionTenant, en `docket_test`): crear una base y migrarla por test sería
 *   inviable cuando haya decenas de tablas. NUNCA se clona un tenant de
 *   desarrollo: el tenant lo crean los propios tests.
 * - Cada test corre dentro de una transacción sobre la base del tenant que se
 *   revierte al terminar (DDL incluido: PostgreSQL es transaccional).
 * - Al terminar el proceso se borran la base física y la fila del catálogo.
 *
 * Los tests de dominio no deben escribir en la base central. Para probar el
 * provisioning o el aislamiento entre tenants NO se usa esto: ver
 * tests/Feature/Tenancy/DatabaseIsolationTest.php.
 */
trait UsesTenantDatabase
{
    public Tenant $tenant;

    private static ?string $sharedTenantId = null;

    protected function setUpUsesTenantDatabase(): void
    {
        // Idempotente: otros tests (DatabaseMigrations) pueden haber vaciado la central.
        Artisan::call('migrate', ['--force' => true]);

        $this->tenant = self::sharedTenant();

        tenancy()->initialize($this->tenant);
        DB::connection('tenant')->beginTransaction();
    }

    protected function tearDownUsesTenantDatabase(): void
    {
        $connection = DB::connection('tenant');

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        tenancy()->end();
    }

    private static function sharedTenant(): Tenant
    {
        if (self::$sharedTenantId !== null) {
            $tenant = Tenant::find(self::$sharedTenantId);

            if ($tenant !== null) {
                return $tenant;
            }

            // Otro test vació el catálogo (migrate:fresh): la base física quedó
            // huérfana. Se borra ya y se provisiona un tenant nuevo.
            self::dropSharedTenant();
        }

        $tenant = app(ProvisionTenant::class)->handle(new ProvisionTenantData(
            name: 'Curaduría de pruebas (compartida)',
            subdomain: 'shared-'.Str::lower(Str::random(8)),
        ));

        self::$sharedTenantId = (string) $tenant->getTenantKey();

        $connection = config('database.connections.'.config('tenancy.database.central_connection'));
        $database = $tenant->database()->getName();
        $id = self::$sharedTenantId;

        register_shutdown_function(fn () => self::dropPhysicalDatabase($connection, $database, $id));

        return $tenant;
    }

    private static function dropSharedTenant(): void
    {
        $connection = config('database.connections.'.config('tenancy.database.central_connection'));
        $database = config('tenancy.database.prefix').self::$sharedTenantId.config('tenancy.database.suffix');

        self::dropPhysicalDatabase($connection, $database, (string) self::$sharedTenantId);
        self::$sharedTenantId = null;
    }

    /**
     * PDO directo y no Eloquent: corre en el shutdown, cuando la aplicación de
     * Laravel del último test ya no existe.
     *
     * @param  array<string, mixed>  $connection
     */
    private static function dropPhysicalDatabase(array $connection, string $database, string $tenantId): void
    {
        try {
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', $connection['host'], $connection['port'], $connection['database']),
                $connection['username'],
                $connection['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            // Defensa: jamás borrar algo que no sea una base de tenant de pruebas.
            if (! str_starts_with($database, TestCase::TENANT_DATABASE_PREFIX)) {
                return;
            }

            $pdo->exec(sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', str_replace('"', '', $database)));
            $pdo->prepare('DELETE FROM tenants WHERE id = ?')->execute([$tenantId]);
        } catch (Throwable) {
            // Si falla la limpieza queda para `make test-clean`; no debe romper el reporte de la suite.
        }
    }
}
