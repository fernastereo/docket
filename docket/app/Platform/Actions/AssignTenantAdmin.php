<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\Actions\InviteEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Models\Employee;
use App\Platform\Actions\Concerns\RunsAsTenantAdmin;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Da su administrador a una curaduría que no lo tiene (por ejemplo, una dada de
 * alta antes de que el alta lo exigiera) y le envía la invitación. Es la misma
 * cuenta que crea `ProvisionTenant`: sin contraseña, rol admin. Solo una
 * curaduría sin administrador; a quien ya lo tiene se le envía su enlace.
 */
final class AssignTenantAdmin
{
    use RunsAsTenantAdmin;

    /**
     * @throws ValidationException la curaduría no admite escrituras, ya tiene administrador o los datos no son válidos
     */
    public function handle(Tenant $tenant, string $name, string $email, string $platformUserId): void
    {
        $this->inTenant($tenant, function () use ($name, $email): void {
            if (Employee::firstAdmin() !== null) {
                throw ValidationException::withMessages(['tenant' => __('This curaduría already has an administrator.')]);
            }

            try {
                $admin = app(CreateEmployee::class)->handle(new CreateEmployeeData($name, $email, [EmployeeRole::Admin]));
            } catch (ValidationException $e) {
                // Los errores se muestran junto a los campos del formulario del panel.
                throw ValidationException::withMessages(
                    collect($e->errors())->mapWithKeys(fn (array $messages, string $field) => ["admin_{$field}" => $messages])->all(),
                );
            }

            app(InviteEmployee::class)->handle($admin);
        }, writes: true);

        Log::notice('Un usuario de plataforma asignó el administrador de una curaduría', [
            'tenant_id' => $tenant->id,
            'platform_user_id' => $platformUserId,
        ]);
    }
}
