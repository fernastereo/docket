<?php

declare(strict_types=1);

namespace App\Http\Resources\Employee;

use App\Domain\Identity\Models\Employee;
use App\Http\Middleware\RequireEmployeeMfa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
final class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames()->values()->all(),
            // Estado del segundo factor: la SPA decide con esto qué pantalla mostrar.
            'mfa_enabled' => $this->hasMfaEnabled(),
            'mfa_required' => $this->mustUseMfa(),
            'mfa_verified' => $request->hasSession() && $request->session()->get(RequireEmployeeMfa::PASSED) === true,
        ];
    }
}
