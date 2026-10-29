<?php

declare(strict_types=1);

namespace App\Support\Mfa;

use Illuminate\Support\Carbon;

/**
 * Cuenta que puede tener segundo factor (TOTP): usuario de plataforma o
 * empleado de curaduría. El modelo guarda las cuatro columnas `mfa_*`; el
 * servicio Mfa y las Actions solo dependen de este contrato.
 *
 * @property string|null $mfa_secret
 * @property Carbon|null $mfa_confirmed_at
 * @property int|null $mfa_last_used_timestamp
 * @property list<string>|null $mfa_recovery_codes HMAC de cada código, no el código
 */
interface MfaSubject
{
    /** MFA activa = secreto confirmado con un código (no solo generado). */
    public function hasMfaEnabled(): bool;

    /** Texto con el que la app de autenticación rotula la entrada. */
    public function mfaAccountName(): string;

    /** Clave del límite de intentos; distinta por dominio de identidad y por cuenta. */
    public function mfaThrottleKey(): string;
}
