<?php

declare(strict_types=1);

namespace App\Platform\Queries;

use App\Domain\Identity\Models\Employee;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Quién es el administrador de una curaduría, para el listado del panel. Los
 * empleados viven en la base de cada curaduría (ADR-005): la central no guarda
 * ese dato, así que se lee de la base del tenant al listar. Una curaduría
 * caída o sin migrar no debe romper el listado de las demás: ahí es `null`.
 */
final class TenantAdminSummary
{
    /**
     * @return array{name: string, email: string, invitation_pending: bool}|null
     */
    public function for(Tenant $tenant): ?array
    {
        if (! $tenant->status->isAccessible()) {
            return null;
        }

        // El try/catch va DENTRO del closure: una excepción que escapa de
        // run() deja la tenancy inicializada.
        return $tenant->run(function () use ($tenant): ?array {
            try {
                $admin = Employee::firstAdmin();
            } catch (Throwable $e) {
                Log::warning('No se pudo leer el administrador de la curaduría', ['tenant_id' => $tenant->id, 'exception' => $e]);

                return null;
            }

            return $admin === null ? null : [
                'name' => $admin->name,
                'email' => $admin->email,
                // Sin contraseña = todavía no aceptó la invitación.
                'invitation_pending' => $admin->password === null,
            ];
        });
    }
}
