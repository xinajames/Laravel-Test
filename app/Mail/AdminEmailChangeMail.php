<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEmailChangeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private $user,
        private $generatedPassword,
        private $langData
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notification.adminEmailChange.mailSubject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $data = [
            'user' => $this->user,
            'generatedPassword' => $this->generatedPassword,
            'url' => route('login'),
        ];

        return new Content(
            markdown: 'mail.admins.email_changed',
            with: $data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
