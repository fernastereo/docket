<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\PlatformUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Única vía para fijar la contraseña de un usuario de plataforma ya existente.
 * Política de plataforma (politicas.md §2): 12 caracteres como mínimo y chequeo
 * contra filtraciones. Anota cuándo cambió: las sesiones abiertas antes de ese
 * momento se cierran (EnforcePlatformSessionLimits).
 */
final class SetPlatformUserPassword
{
    /**
     * @throws ValidationException la contraseña no cumple la política
     */
    public function handle(PlatformUser $user, #[\SensitiveParameter] string $password): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::min((int) config('platform.password_min_length'))->uncompromised()]],
        )->validate();

        $user->forceFill(['password' => $password, 'password_changed_at' => now()])->save();
    }
}
