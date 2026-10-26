<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\Actions\SeedEmployeeRoles;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Enums\TenantStatus;
use App\Platform\Exceptions\TenantProvisioningFailed;
use App\Platform\Models\Tenant;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

/**
 * Alta de una curaduría: catálogo + dominio + base PostgreSQL + migraciones
 * (ADR-002, ADR-010). Única vía de escritura para crear tenants (ADR-011).
 *
 * El pipeline automático de stancl (evento TenantCreated) está desactivado
 * en TenancyServiceProvider a propósito: el orden y la compensación de
 * fallos se controlan acá, no en eventos.
 *
 * `CREATE DATABASE` no corre dentro de una transacción de PostgreSQL, así que
 * un rollback no cubre la base física: ante un fallo se compensa a mano.
 */
final class ProvisionTenant
{
    /**
     * @throws ValidationException si los datos no cumplen las reglas de negocio
     * @throws TenantProvisioningFailed si falla la creación; el tenant queda en `failed`
     */
    public function handle(ProvisionTenantData $data): Tenant
    {
        $this->validate($data);

        $tenant = Tenant::create(['name' => $data->name]);

        try {
            $tenant->domains()->create(['domain' => TenantSubdomain::hostFor($data->subdomain)]);

            Bus::dispatchSync(new CreateDatabase($tenant));
            $this->migrate($tenant);
            $this->bootstrapIdentity($tenant, $data);
        } catch (Throwable $e) {
            $this->compensate($tenant, $e);

            throw TenantProvisioningFailed::for($tenant, $e);
        }

        $tenant->update([
            'status' => $data->status,
            'provisioned_at' => now(),
        ]);

        return $tenant;
    }

    private function validate(ProvisionTenantData $data): void
    {
        Validator::make(
            [
                'name' => $data->name,
                'subdomain' => $data->subdomain,
                'status' => $data->status->value,
                'admin_name' => $data->adminName,
                'admin_email' => $data->adminEmail,
            ],
            [
                'name' => ['required', 'string', 'min:3', 'max:150'],
                'subdomain' => ['required', new TenantSubdomain],
                // Un tenant nace operable: demo o activo. El resto son estados de ciclo de vida.
                'status' => [Rule::in([TenantStatus::Demo->value, TenantStatus::Active->value])],
                // El primer administrador se da completo o no se da.
                'admin_name' => ['nullable', 'required_with:admin_email', 'string', 'min:3', 'max:150'],
                'admin_email' => ['nullable', 'required_with:admin_name', 'email:rfc', 'max:254'],
            ],
        )->validate();
    }

    private function migrate(Tenant $tenant): void
    {
        // MigrateDatabase de stancl ignora el código de salida; acá se verifica.
        $exitCode = Artisan::call('tenants:migrate', ['--tenants' => [$tenant->getTenantKey()]]);

        if ($exitCode !== 0) {
            throw new RuntimeException('tenants:migrate terminó con código '.$exitCode.': '.trim(Artisan::output()));
        }
    }

    /**
     * Roles de la curaduría y, si se indicó, su primer administrador. Corre
     * dentro de la base del tenant recién migrada. El administrador nace sin
     * contraseña: la fija al aceptar su invitación.
     */
    private function bootstrapIdentity(Tenant $tenant, ProvisionTenantData $data): void
    {
        $tenant->run(function () use ($data): void {
            app(SeedEmployeeRoles::class)->handle();

            if ($data->adminEmail !== null && $data->adminName !== null) {
                app(CreateEmployee::class)->handle(new CreateEmployeeData(
                    name: $data->adminName,
                    email: $data->adminEmail,
                    roles: [EmployeeRole::Admin],
                ));
            }
        });
    }

    /**
     * Deja el sistema consistente tras un fallo: sin base a medias y con el
     * subdominio libre. El tenant se conserva en `failed` para diagnóstico;
     * reintentar es un alta nueva (otro UUID, otra base), nunca reutilizar.
     */
    private function compensate(Tenant $tenant, Throwable $cause): void
    {
        Log::error('Fallo el alta de tenant', [
            'tenant_id' => $tenant->getTenantKey(),
            'exception' => $cause,
        ]);

        try {
            $manager = $tenant->database()->manager();

            if ($manager->databaseExists($tenant->database()->getName())) {
                $manager->deleteDatabase($tenant);
            }

            $tenant->domains()->delete();
        } catch (Throwable $cleanup) {
            // La limpieza fallida no debe tapar la causa original, pero sí quedar registrada.
            Log::critical('No se pudo limpiar un alta de tenant fallida', [
                'tenant_id' => $tenant->getTenantKey(),
                'exception' => $cleanup,
            ]);
        }

        $tenant->update([
            'status' => TenantStatus::Failed,
            'failure_reason' => mb_substr($cause->getMessage(), 0, 1000),
        ]);
    }
}
