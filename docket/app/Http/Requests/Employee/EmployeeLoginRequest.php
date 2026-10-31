<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Domain\Identity\DataTransferObjects\EmployeeLoginData;
use Illuminate\Foundation\Http\FormRequest;

final class EmployeeLoginRequest extends FormRequest
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

    public function toData(): EmployeeLoginData
    {
        return new EmployeeLoginData(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            ip: (string) $this->ip(),
        );
    }
}
