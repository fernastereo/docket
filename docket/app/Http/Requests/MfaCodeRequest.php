<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Un código del segundo factor: TOTP de 6 dígitos o código de recuperación. */
final class MfaCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la barrera es la sesión autenticada (`auth:platform` o `auth:employee`); el código se valida en la Action
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }
}
