<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Mfa\MfaSubject;
use Database\Factories\PlatformUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Soporte / superadmin del proveedor. Vive en la base central y se autentica
 * con el guard `platform` (ADR-005, amendment 2026-10-18). No es un empleado
 * de curaduría (esos viven en la base de cada tenant) ni un solicitante.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $disabled_at
 * @property Carbon|null $last_login_at
 * @property string|null $mfa_secret
 * @property Carbon|null $mfa_confirmed_at
 * @property int|null $mfa_last_used_timestamp
 * @property list<string>|null $mfa_recovery_codes HMAC de cada código, no el código
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'mfa_secret', 'mfa_recovery_codes'])]
#[UseFactory(PlatformUserFactory::class)]
class PlatformUser extends Authenticatable implements MfaSubject
{
    /** @use HasFactory<PlatformUserFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'disabled_at' => 'datetime',
            'last_login_at' => 'datetime',
            'mfa_secret' => 'encrypted',
            'mfa_confirmed_at' => 'datetime',
            'mfa_last_used_timestamp' => 'integer',
            'mfa_recovery_codes' => 'array',
        ];
    }

    /** MFA activa = secreto confirmado con un código (no solo generado). */
    public function hasMfaEnabled(): bool
    {
        return $this->mfa_secret !== null && $this->mfa_confirmed_at !== null;
    }

    public function mfaAccountName(): string
    {
        return $this->email;
    }

    public function mfaThrottleKey(): string
    {
        return 'platform-mfa|'.$this->id;
    }

    /** El email se compara siempre en minúsculas: un solo formato en la base. */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }
}
