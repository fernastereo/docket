<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Domain\Identity\Actions\InviteEmployee;
use App\Domain\Identity\Actions\SendEmployeePasswordReset;
use App\Domain\Identity\Models\Employee;
use App\Platform\Actions\Concerns\RunsAsTenantAdmin;
use App\Platform\Models\Tenant;
use Illuminate\Validation\ValidationException;

/**
 * Envía al administrador de una curaduría su enlace de acceso: la invitación si
 * aún no tiene contraseña, o un enlace de recuperación si ya la tiene. En ambos
 * casos el enlace anterior sin usar queda invalidado.
 */
final class SendTenantAdminLink
{
    use RunsAsTenantAdmin;

    /**
     * @throws ValidationException
     */
    public function handle(Tenant $tenant): void
    {
        $this->asAdmin($tenant, function (Employee $admin): void {
            $admin->password === null
                ? app(InviteEmployee::class)->handle($admin)
                : app(SendEmployeePasswordReset::class)->handle($admin);
        });
    }
}
