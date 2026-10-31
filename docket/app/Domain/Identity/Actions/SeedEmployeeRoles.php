<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\EmployeeRole;
use Spatie\Permission\Models\Role;

/**
 * Crea en la base del tenant actual los roles de EmployeeRole. Idempotente:
 * se puede repetir (p. ej. cuando se agregue un rol nuevo al enum). Debe
 * correr con la tenancy inicializada (`$tenant->run(...)`): escribe en la base
 * del tenant, nunca en la central.
 */
final class SeedEmployeeRoles
{
    public function handle(): void
    {
        foreach (EmployeeRole::cases() as $role) {
            Role::findOrCreate($role->value, 'employee');
        }
    }
}
