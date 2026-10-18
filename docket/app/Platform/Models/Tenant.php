<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Platform\Enums\TenantStatus;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Catálogo de curadurías en la base central (ADR-002, ADR-010). El modelo
 * base de stancl/tenancy no trae la relación `domains()` ni la gestión de
 * base de datos por sí solo — hace falta este modelo propio con los traits
 * habilitados (ver tenancyforlaravel.com/docs/v3/tenants).
 *
 * @property string $name
 * @property TenantStatus $status
 * @property string|null $failure_reason
 * @property Carbon|null $provisioned_at
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Columnas reales de la tabla `tenants`. stancl guarda en el JSON `data`
     * cualquier atributo que NO figure aquí, aunque la columna exista: si se
     * agrega una columna nueva a la tabla, hay que declararla también acá.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'failure_reason',
            'provisioned_at',
            'created_at',
            'updated_at',
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'provisioned_at' => 'datetime',
        ];
    }
}
