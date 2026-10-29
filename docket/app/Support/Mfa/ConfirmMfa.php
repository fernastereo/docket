<?php

declare(strict_types=1);

namespace App\Support\Mfa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;

/**
 * Segundo paso de la activación: la persona demuestra que su app genera códigos
 * válidos con el secreto. Recién ahí MFA queda activa y se emiten los códigos
 * de recuperación (devueltos una sola vez).
 */
final class ConfirmMfa
{
    public function __construct(private readonly Mfa $mfa) {}

    /**
     * @return list<string> códigos de recuperación en claro
     *
     * @throws ValidationException código inválido, o no hay activación en curso / ya está activa
     * @throws ThrottleRequestsException demasiados intentos
     */
    public function handle(Model&MfaSubject $user, string $code): array
    {
        if ($user->hasMfaEnabled() || $user->getAttribute('mfa_secret') === null) {
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
