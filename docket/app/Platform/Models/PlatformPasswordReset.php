<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\SingleUseToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enlace de un solo uso para que un usuario de plataforma elija una contraseña
 * nueva. Solo se guarda el hash del token; el token en claro viaja únicamente en
 * el correo.
 *
 * @property string $id
 * @property string $platform_user_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property-read PlatformUser $platformUser
 */
class PlatformPasswordReset extends Model
{
    use HasUuids, SingleUseToken;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PlatformUser, $this> */
    public function platformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class);
    }

    /**
     * Emite un enlace nuevo y devuelve el token en claro (única vez que existe).
     * Los enlaces anteriores sin usar quedan invalidados.
     */
    public static function issueFor(PlatformUser $user, int $minutes): string
    {
        $token = Str::random(64);

        DB::transaction(function () use ($user, $token, $minutes): void {
            self::query()->where('platform_user_id', $user->id)->whereNull('used_at')->delete();

            self::create([
                'platform_user_id' => $user->id,
                'token_hash' => self::hashToken($token),
                'expires_at' => now()->addMinutes($minutes),
            ]);
        });

        return $token;
    }
}
