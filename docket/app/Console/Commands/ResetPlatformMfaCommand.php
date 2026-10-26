<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Platform\Models\PlatformUser;
use Illuminate\Console\Command;

/**
 * Quita el MFA de un usuario de plataforma que perdió su teléfono y sus códigos
 * de recuperación. Es deliberadamente un comando de consola (acceso al
 * servidor) y no un flujo de la app: nadie puede "recuperar" MFA desde fuera.
 * En el próximo login se le exige activarlo de nuevo.
 */
final class ResetPlatformMfaCommand extends Command
{
    protected $signature = 'platform:reset-mfa {email : Email del usuario de plataforma}';

    protected $description = 'Desactiva el MFA de un usuario de plataforma (deberá activarlo de nuevo al entrar)';

    public function handle(): int
    {
        $user = PlatformUser::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->error('No existe un usuario de plataforma con ese email.');

            return self::FAILURE;
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_confirmed_at' => null,
            'mfa_last_used_timestamp' => null,
            'mfa_recovery_codes' => null,
        ])->save();

        $this->info("MFA desactivado para {$user->email}. Deberá activarlo de nuevo en su próximo acceso.");

        return self::SUCCESS;
    }
}
