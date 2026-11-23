<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\PlatformPasswordReset;
use App\Platform\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El usuario de plataforma elige su contraseña nueva con el enlace del correo. No
 * inicia sesión (entra por el login normal, donde se le pide la MFA) y no la toca.
 */
final class AcceptPlatformPasswordReset
{
    /**
     * @throws ValidationException la contraseña no cumple la política, o el enlace ya no vale
     */
    public function handle(PlatformPasswordReset $reset, #[\SensitiveParameter] string $password): PlatformUser
    {
        return DB::transaction(function () use ($reset, $password): PlatformUser {
            // Con bloqueo: dos envíos simultáneos del mismo enlace no pueden ganar ambos.
            $locked = PlatformPasswordReset::query()->usable()->whereKey($reset->id)->lockForUpdate()->first();

            if ($locked === null || $locked->platformUser->isDisabled()) {
                throw ValidationException::withMessages(['token' => __('This link is no longer valid.')]);
            }

            $user = $locked->platformUser;

            app(SetPlatformUserPassword::class)->handle($user, $password);

            $locked->forceFill(['used_at' => now()])->save();

            return $user;
        });
    }
}
