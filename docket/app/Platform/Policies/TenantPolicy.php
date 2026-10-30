<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Platform\Models\PlatformUser;
use App\Platform\Models\Tenant;

/**
 * Quién puede administrar el catálogo de curadurías. Hoy todo usuario de
 * plataforma autenticado puede (no hay roles dentro de plataforma todavía);
 * cuando existan, la distinción (soporte vs superadmin) se hace acá y en
 * ningún otro lado. El guard `platform` ya garantiza que quien llega es un
 * PlatformUser, nunca un empleado ni un solicitante.
 */
final class TenantPolicy
{
    public function viewAny(PlatformUser $user): bool
    {
        return ! $user->isDisabled();
    }

    public function create(PlatformUser $user): bool
    {
        return ! $user->isDisabled();
    }

    public function update(PlatformUser $user, Tenant $tenant): bool
    {
        return ! $user->isDisabled();
    }
}
