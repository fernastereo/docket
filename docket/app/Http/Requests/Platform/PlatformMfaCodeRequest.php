<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/** Un código del segundo factor: TOTP de 6 dígitos o código de recuperación. */
final class PlatformMfaCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la barrera es `auth:platform`; el código se valida en la Action
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
