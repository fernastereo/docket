<?php

declare(strict_types=1);

namespace App\Domain\Identity\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enlace para elegir una contraseña nueva. Igual que la invitación: solo texto
 * plano, cola cifrada porque el enlace lleva el token.
 */
final class EmployeePasswordResetMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $employeeName,
        public readonly string $tenantName,
        public readonly string $url,
        public readonly int $validDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Restablece tu contraseña de {$this->tenantName} en CuraduriAPP");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.employee-password-reset');
    }
}
