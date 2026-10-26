<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Http\Middleware\RequirePlatformMfa;
use App\Platform\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PlatformUser
 */
final class PlatformUserResource extends JsonResource
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
            // Estado del segundo factor: la SPA decide con esto qué pantalla mostrar.
            'mfa_enabled' => $this->hasMfaEnabled(),
            'mfa_verified' => $request->hasSession() && $request->session()->get(RequirePlatformMfa::PASSED) === true,
        ];
    }
}
