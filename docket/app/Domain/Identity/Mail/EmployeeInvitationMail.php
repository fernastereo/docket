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
 * Correo de invitación. Solo lleva texto plano (nada de modelos): sale por la
 * cola sin depender del contexto de tenant. El enlace contiene el token, por
 * eso el trabajo encolado va cifrado.
 */
final class EmployeeInvitationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
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
        return new Envelope(subject: "Invitación a {$this->tenantName} en CuraduriAPP");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.employee-invitation');
    }
}
