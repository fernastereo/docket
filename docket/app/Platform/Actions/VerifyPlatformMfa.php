<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Mfa\PlatformMfa;
use App\Platform\Models\PlatformUser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;

/**
 * Segundo paso del login: acepta un código TOTP de 6 dígitos o un código de
 * recuperación (de un solo uso). No toca la sesión: marcarla como verificada
 * es del controlador.
 */
final class VerifyPlatformMfa
{
    public function __construct(private readonly PlatformMfa $mfa) {}

    /**
     * @throws ValidationException código inválido, o el usuario no tiene MFA activa
     * @throws ThrottleRequestsException demasiados intentos
     */
    public function handle(PlatformUser $user, string $code): void
    {
        if (! $user->hasMfaEnabled()) {
            throw ValidationException::withMessages(['mfa' => __('Two-factor authentication is not enabled.')]);
        }

        $this->mfa->ensureNotLocked($user);

        $code = trim($code);
        $valid = preg_match('/^\d{6}$/', $code) === 1
            ? $this->mfa->verifyTotp($user, $code)
            : $this->mfa->consumeRecoveryCode($user, $code);

        if (! $valid) {
            $this->mfa->fail($user);
        }

        $this->mfa->clearAttempts($user);
    }
}
