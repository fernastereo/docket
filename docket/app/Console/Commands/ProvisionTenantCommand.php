<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Platform\Actions\ProvisionTenant;
use App\Platform\Data\ProvisionTenantData;
use App\Platform\Enums\TenantStatus;
use App\Platform\Exceptions\TenantProvisioningFailed;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Primera puerta de entrada de ProvisionTenant (ADR-010: "provisioning = un
 * comando"). Solo traduce argumentos a DTO; la lógica vive en la Action, que
 * el panel de plataforma reutilizará sin cambios.
 */
class ProvisionTenantCommand extends Command
{
    protected $signature = 'tenants:provision
        {name : Nombre de la curaduría (p. ej. "Curaduría Urbana 1 de Bogotá")}
        {subdomain : Subdominio, en minúsculas (p. ej. cu1bogota)}
        {--active : Crear directamente como activo en lugar de demo}';

    protected $description = 'Da de alta una curaduría: catálogo, dominio, base PostgreSQL y migraciones';

    public function handle(ProvisionTenant $provisionTenant): int
    {
        $data = new ProvisionTenantData(
            name: trim((string) $this->argument('name')),
            subdomain: Str::lower(trim((string) $this->argument('subdomain'))),
            status: $this->option('active') ? TenantStatus::Active : TenantStatus::Demo,
        );

        try {
            $tenant = $provisionTenant->handle($data);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (TenantProvisioningFailed $e) {
            $this->error($e->getMessage());
            $this->line('El tenant quedó en estado "failed" y la base fue limpiada. Revisa los logs y vuelve a intentar.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Curaduría "%s" creada (%s). Host: %s · id: %s',
            $tenant->name,
            $tenant->status->value,
            TenantSubdomain::hostFor($data->subdomain),
            $tenant->getTenantKey(),
        ));

        return self::SUCCESS;
    }
}
