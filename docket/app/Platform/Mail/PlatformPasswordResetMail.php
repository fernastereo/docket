<?php

declare(strict_types=1);

namespace App\Platform\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enlace para elegir una contraseña nueva en el panel de plataforma. Solo texto
 * plano; la cola va cifrada porque el enlace lleva el token.
 */
final class PlatformPasswordResetMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $url,
        public readonly int $validMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Restablece tu contraseña del panel de CuraduriAPP');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.platform-password-reset');
    }
}
