<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad "por defecto" (ADR-017, capa 3 — "seguro por
 * defecto", no un añadido posterior). Caddy ya pone HSTS / nosniff /
 * Referrer-Policy en el borde (infra/docker/Caddyfile.*); esto es la segunda
 * capa a nivel de aplicación, y el único lugar donde puede vivir la CSP con
 * nonce (el nonce también se inyecta en las etiquetas <script>/<style> que
 * genera la directiva @vite).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-Frame-Options', 'DENY');

        // La API responde JSON; la CSP solo aplica a documentos HTML (la SPA).
        // Y solo a respuestas 2xx: las páginas de error (500 de debug, 404,
        // etc.) son vistas del propio framework con <style> inline sin
        // nuestro nonce — aplicarles esta CSP las deja sin estilos, sin
        // ganar nada en seguridad (no son la superficie que protegemos).
        if (! $request->is('api/*') && $response->isSuccessful()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "img-src 'self' data: https://*.digitaloceanspaces.com",
                "font-src 'self'",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        return $response;
    }
}
