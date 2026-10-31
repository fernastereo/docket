<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Domain\Identity\Actions\ResetEmployeeMfa;
use App\Domain\Identity\Models\Employee;
use App\Platform\Actions\Concerns\RunsAsTenantAdmin;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * El operador de la plataforma reinicia la verificación en dos pasos del
 * administrador de una curaduría que perdió su app y sus códigos de recuperación.
 * La contraseña no se toca; en su próximo acceso tiene que activarla de nuevo.
 * Queda constancia en el log de quién lo hizo.
 */
final class ResetTenantAdminMfa
{
    use RunsAsTenantAdmin;

    /**
     * @throws ValidationException la curaduría no admite escrituras o no tiene administrador
     */
    public function handle(Tenant $tenant, string $platformUserId): void
    {
        $this->asAdmin($tenant, function (Employee $admin): void {
            app(ResetEmployeeMfa::class)->handle($admin);
        }, writes: true);

        Log::notice('Un usuario de plataforma reinició el MFA del administrador de una curaduría', [
            'tenant_id' => $tenant->id,
            'platform_user_id' => $platformUserId,
        ]);
    }
}
