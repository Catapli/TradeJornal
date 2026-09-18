<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * El resumen del domingo.
 *
 * Va a la cola (`ShouldQueue`) porque el comando recorre a todos los usuarios de
 * una tacada: si el proveedor de correo tarda o falla con uno, el resto de la
 * tanda no puede quedarse sin salir.
 *
 * El enlace de baja se firma para que funcione sin sesión — el correo se lee en
 * el móvil, muchas veces sin estar dentro de la aplicación — y caduca a los 30
 * días, que es más de lo que vive el correo en la bandeja de entrada.
 */
class WeeklySummaryMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public array $summary,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('weekly.mail.subject', [
                'week' => $this->weekLabel(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary',
            with: [
                'summary' => $this->summary,
                'user' => $this->user,
                'reviewUrl' => route('weekly.review'),
                'unsubscribeUrl' => URL::temporarySignedRoute(
                    'weekly.unsubscribe',
                    now()->addDays(30),
                    ['user' => $this->user->id],
                ),
                'weekLabel' => $this->weekLabel(),
            ],
        );
    }

    /** «18 – 24 ago», tal y como se lee en el asunto y en la cabecera. */
    private function weekLabel(): string
    {
        $start = CarbonImmutable::parse($this->summary['week_start']);
        $end = CarbonImmutable::parse($this->summary['week_end']);

        return $start->translatedFormat('j M') . ' – ' . $end->translatedFormat('j M');
    }
}
