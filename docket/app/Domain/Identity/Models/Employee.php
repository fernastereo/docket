<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\EmployeeRole;
use App\Domain\Identity\Enums\EmployeeStatus;
use App\Support\Mfa\MfaSubject;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * Cuenta de empleado de una curaduría. Vive en la base del TENANT (no en la
 * central) y absorbe la membresía: credencial + roles + vigencia + estado
 * (ADR-005, amendment 2026-09-02). Ninguna otra curaduría ni la central pueden
 * saber que esta persona trabaja aquí.
 *
 * `password` es null hasta que la persona acepta la invitación y fija la suya:
 * una cuenta sin contraseña existe pero no puede entrar.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property EmployeeStatus $status
 * @property Carbon $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon|null $last_login_at
 * @property string|null $mfa_secret
 * @property Carbon|null $mfa_confirmed_at
 * @property int|null $mfa_last_used_timestamp
 * @property list<string>|null $mfa_recovery_codes HMAC de cada código, no el código
 */
#[Fillable(['name', 'email', 'password', 'valid_from', 'valid_until'])]
#[Hidden(['password', 'mfa_secret', 'mfa_recovery_codes'])]
class Employee extends Authenticatable implements MfaSubject
{
    use HasRoles, HasUuids;

    /** Los roles de spatie se guardan bajo este guard (config/auth.php). */
    protected string $guard_name = 'employee';

    /** La base también lo define; acá para que el objeto recién creado ya lo tenga. */
    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => EmployeeStatus::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'last_login_at' => 'datetime',
            'mfa_secret' => 'encrypted',
            'mfa_confirmed_at' => 'datetime',
            'mfa_last_used_timestamp' => 'integer',
            'mfa_recovery_codes' => 'array',
        ];
    }

    /** Alguno de sus roles exige MFA (curador, admin): ADR-017, politicas.md §2. */
    public function mustUseMfa(): bool
    {
        $roles = array_map(
            fn (EmployeeRole $role) => $role->value,
            array_filter(EmployeeRole::cases(), fn (EmployeeRole $role) => $role->requiresMfa()),
        );

        return $this->hasAnyRole($roles);
    }

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
        return 'employee-mfa|'.$this->id;
    }

    /** El administrador más antiguo de la curaduría (el primero que se dio de alta). Requiere tenancy. */
    public static function firstAdmin(): ?self
    {
        return self::query()->role(EmployeeRole::Admin->value)->oldest()->first();
    }

    /** @return HasMany<EmployeeInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(EmployeeInvitation::class);
    }

    /** El email se compara siempre en minúsculas: un solo formato en la base. */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    /** Activa y dentro de su vigencia (desde/hasta, ambos inclusive). */
    public function isActive(): bool
    {
        if ($this->status !== EmployeeStatus::Active) {
            return false;
        }

        $today = now()->startOfDay();

        return $this->valid_from->startOfDay()->lte($today)
            && ($this->valid_until === null || $this->valid_until->endOfDay()->gte($today));
    }
}
