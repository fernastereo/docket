<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos del administrador que se asigna a una curaduría. Acá solo la forma de la
 * petición; largo y formato los valida la Action `CreateEmployee`.
 */
final class AssignTenantAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform')?->can('update', $this->route('tenant')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'admin_name' => ['required', 'string'],
            'admin_email' => ['required', 'string'],
        ];
    }

    public function adminName(): string
    {
        return trim($this->string('admin_name')->toString());
    }

    public function adminEmail(): string
    {
        return trim($this->string('admin_email')->toString());
    }
}
