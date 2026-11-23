<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Enlace de un solo uso que llega por correo (invitación, recuperación de
 * contraseña). El modelo tiene `token_hash`, `expires_at` y `used_at`; solo se
 * guarda el hash del token, el token en claro existe únicamente en el correo.
 */
trait SingleUseToken
{
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Sin usar y sin vencer.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    /** El enlace vigente que corresponde a este token, si existe. */
    public static function findUsable(string $token): ?static
    {
        return static::query()->usable()->where('token_hash', static::hashToken($token))->first();
    }
}
