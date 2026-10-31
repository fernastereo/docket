<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Contraseña nueva para el administrador de una curaduría. La política (largo,
 * brechas) la valida la Action; acá solo la forma de la petición.
 */
final class SetTenantAdminPasswordRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:1024', 'confirmed'],
        ];
    }

    public function password(): string
    {
        return $this->string('password')->toString();
    }
}
