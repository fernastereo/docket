<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // público; la barrera es el token del enlace
    }

    /**
     * La política de contraseña (largo, brechas) se valida en la Action; acá
     * solo la forma de la petición.
     *
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
