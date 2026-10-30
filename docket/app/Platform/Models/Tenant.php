<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Platform\Enums\TenantStatus;
use App\Platform\Policies\TenantPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
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
 * @property-read Collection<int, Domain> $domains
 *
 * @method HasMany<Domain, $this> domains()
 */
#[UsePolicy(TenantPolicy::class)]
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
    /**
     * URL absoluta de esta curaduría (su primer dominio), p. ej. para los
     * enlaces de los correos. Siempre https; en local lleva el puerto de
     * PLATFORM_TENANT_URL_PORT.
     */
    public function url(string $path = '/'): string
    {
        $domain = $this->domains()->orderBy('created_at')->firstOrFail()->domain;
        $port = config('platform.tenant_url_port');

        return 'https://'.$domain.($port ? ':'.$port : '').'/'.ltrim($path, '/');
    }

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
