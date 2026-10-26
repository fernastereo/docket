<?php

namespace Database\Factories;

use App\Platform\Models\PlatformUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformUser>
 */
class PlatformUserFactory extends Factory
{
    protected $model = PlatformUser::class;

    /**
     * La contraseña por defecto cumple la política de plataforma (12+
     * caracteres) y no figura en listas de brechas conocidas.
     */
    public const PASSWORD = 'plataforma-prueba-9f3k-Zq72';

    /** Secreto TOTP fijo para pruebas (base32, 32 caracteres). */
    public const MFA_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => self::PASSWORD,
        ];
    }

    /** Con MFA activa (secreto MFA_SECRET) y sin códigos de recuperación. */
    public function withMfa(): static
    {
        return $this->state(fn () => [
            'mfa_secret' => self::MFA_SECRET,
            'mfa_confirmed_at' => now(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['disabled_at' => now()]);
    }
}
