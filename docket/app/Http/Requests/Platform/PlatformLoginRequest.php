<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Platform\Data\PlatformLoginData;
use Illuminate\Foundation\Http\FormRequest;

final class PlatformLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el login es público; la barrera es la credencial
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
        ];
    }

    public function toData(): PlatformLoginData
    {
        return new PlatformLoginData(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            ip: (string) $this->ip(),
        );
    }
}
