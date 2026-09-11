<?php

declare(strict_types=1);

namespace App\Platform\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Catálogo de curadurías en la base central (ADR-002, ADR-010). El modelo
 * base de stancl/tenancy no trae la relación `domains()` ni la gestión de
 * base de datos por sí solo — hace falta este modelo propio con los traits
 * habilitados (ver tenancyforlaravel.com/docs/v3/tenants).
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;
}
