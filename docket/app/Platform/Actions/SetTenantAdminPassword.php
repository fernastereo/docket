<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Domain\Identity\Actions\SetEmployeePassword;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use App\Platform\Actions\Concerns\RunsAsTenantAdmin;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * El operador de la plataforma fija la contraseña del administrador de una
 * curaduría. La política es la de siempre (SetEmployeePassword); los enlaces
 * pendientes se anulan para que uno viejo no la pise. La MFA del administrador
 * no se toca: sigue siendo exigida en el login. Queda constancia en el log de
 * quién lo hizo (nunca la contraseña).
 */
final class SetTenantAdminPassword
{
    use RunsAsTenantAdmin;

    /**
     * @throws ValidationException la curaduría no admite escrituras, no tiene administrador o la contraseña no cumple la política
     */
    public function handle(Tenant $tenant, #[\SensitiveParameter] string $password, string $platformUserId): void
    {
        $this->asAdmin($tenant, function (Employee $admin) use ($password): void {
            app(SetEmployeePassword::class)->handle($admin, $password);

            EmployeeInvitation::query()->where('employee_id', $admin->id)->whereNull('used_at')->delete();
        }, writes: true);

        Log::notice('Un usuario de plataforma fijó la contraseña del administrador de una curaduría', [
            'tenant_id' => $tenant->id,
            'platform_user_id' => $platformUserId,
        ]);
    }
}
