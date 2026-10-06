<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoVerificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $codigo,
        public int $minutos,
        public int $pedidoId,
        public string $monto,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código de CafeQR: ' . $this->codigo);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.codigo');
    }
}