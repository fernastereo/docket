<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // público: es el paso previo a poder entrar
    }

    /**
     * Solo la forma: si existe o no una cuenta con ese correo no se dice nunca.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
