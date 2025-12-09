<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenerateReportMail extends Mailable
{
    use Queueable, SerializesModels;

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
            subject: __('notification.generateReport.mailSubject', ['reportName' => $this->langData['report_name']]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $data = [
            'user' => $this->user,
            'url' => $this->langData['url'],
            'report_name' => $this->langData['report_name'],
            'file_name' => $this->langData['file_name'],
            'status' => $this->langData['status'],
            'updated_at' => $this->langData['updated_at'],
            'is_successful' => $this->langData['is_successful'],
        ];

        return new Content(
            markdown: 'mail.reports.generated',
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
