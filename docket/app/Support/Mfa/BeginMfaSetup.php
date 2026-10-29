<?php

declare(strict_types=1);

namespace App\Support\Mfa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Primer paso de la activación de MFA: genera un secreto TOTP nuevo y lo guarda
 * (cifrado) SIN activarlo. Solo se activa al confirmarlo con un código
 * (ConfirmMfa). Repetirlo antes de confirmar reemplaza el secreto.
 */
final class BeginMfaSetup
{
    public function __construct(private readonly Mfa $mfa) {}

    /**
     * @return array{secret: string, uri: string, qr: string}
     *
     * @throws ValidationException si el usuario ya tiene MFA activa
     */
    public function handle(Model&MfaSubject $user): array
    {
        if ($user->hasMfaEnabled()) {
            throw ValidationException::withMessages(['mfa' => __('Two-factor authentication is already enabled.')]);
        }

        $secret = $this->mfa->newSecret();
        $user->forceFill(['mfa_secret' => $secret, 'mfa_confirmed_at' => null, 'mfa_last_used_timestamp' => null])->save();

        $uri = $this->mfa->provisioningUri($user, $secret);

        return ['secret' => $secret, 'uri' => $uri, 'qr' => $this->mfa->qrDataUri($uri)];
    }
}
