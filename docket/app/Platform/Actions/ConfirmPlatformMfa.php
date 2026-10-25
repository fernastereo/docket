<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Mfa\PlatformMfa;
use App\Platform\Models\PlatformUser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;

/**
 * Segundo paso de la activación: la persona demuestra que su app genera códigos
 * válidos con el secreto. Recién ahí MFA queda activa y se emiten los códigos
 * de recuperación (devueltos una sola vez).
 */
final class ConfirmPlatformMfa
{
    public function __construct(private readonly PlatformMfa $mfa) {}

    /**
     * @return list<string> códigos de recuperación en claro
     *
     * @throws ValidationException código inválido, o no hay activación en curso / ya está activa
     * @throws ThrottleRequestsException demasiados intentos
     */
    public function handle(PlatformUser $user, string $code): array
    {
        if ($user->hasMfaEnabled() || $user->mfa_secret === null) {
            throw ValidationException::withMessages(['mfa' => __('There is no pending two-factor setup.')]);
        }

        $this->mfa->ensureNotLocked($user);

        if (! $this->mfa->verifyTotp($user, $code)) {
            $this->mfa->fail($user);
        }

        $this->mfa->clearAttempts($user);
        $user->forceFill(['mfa_confirmed_at' => now()])->save();

        return $this->mfa->issueRecoveryCodes($user);
    }
}
