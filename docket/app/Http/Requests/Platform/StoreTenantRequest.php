<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Platform\Data\ProvisionTenantData;
use App\Platform\Enums\TenantStatus;
use App\Platform\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de una curaduría desde el panel de plataforma. Acá solo se comprueba la
 * forma de la entrada; las reglas de negocio (subdominio libre y no reservado,
 * longitudes) las valida la Action `ProvisionTenant`, que es la misma que usa
 * el comando — así no hay dos fuentes de verdad.
 */
final class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform')?->can('create', Tenant::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'subdomain' => ['required', 'string'],
            'active' => ['sometimes', 'boolean'],
            // Toda curaduría nace con su primer administrador: sin él nadie
            // podría entrar. Longitud y formato los valida la Action.
            'admin_name' => ['required', 'string'],
            'admin_email' => ['required', 'string'],
        ];
    }

    public function toData(): ProvisionTenantData
    {
        return new ProvisionTenantData(
            name: trim($this->string('name')->toString()),
            subdomain: mb_strtolower(trim($this->string('subdomain')->toString())),
            status: $this->boolean('active') ? TenantStatus::Active : TenantStatus::Demo,
            adminName: trim($this->string('admin_name')->toString()),
            adminEmail: trim($this->string('admin_email')->toString()),
        );
    }
}
