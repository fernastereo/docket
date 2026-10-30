<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Platform\Models\Tenant;
use App\Platform\Queries\TenantAdminSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lo que el panel ve de una curaduría. Nunca expone el nombre de la base ni
 * credenciales: solo el catálogo.
 *
 * @mixin Tenant
 */
final class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'host' => $this->domains->first()?->domain,
            'status' => $this->status->value,
            // Se lee de la base de la curaduría (los empleados no viven en la central).
            'admin' => app(TenantAdminSummary::class)->for($this->resource),
            'failure_reason' => $this->failure_reason,
            'provisioned_at' => $this->provisioned_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
