<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderDueMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private $user,
        private $langData
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notification.reminderDue.mailSubject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $data = [
            'user' => $this->user,
            'url' => route('login'),
            'scheduled_at' => $this->langData['scheduled_at'],
            'title' => $this->langData['title'],
            'description' => $this->langData['description'],
            'reference_date' => $this->langData['reference_date'],
            'store_name' => $this->langData['store_name'],
            'days_before' => $this->langData['days_before'],
            'is_manual' => $this->langData['is_manual'],
        ];

        return new Content(
            markdown: 'mail.reminders.due',
            with: $data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
