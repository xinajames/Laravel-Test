<?php

namespace App\Mail\Royalty;

use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroOutput;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenerateRoyaltyMail extends Mailable
{
    use Queueable, SerializesModels;

    private $batch_id;

    private $user;

    private $mailSubject;

    private $title;

    private $message;

    public function __construct($user, $batch_id, $mailSubject, $title, $message)
    {
        $this->batch_id = $batch_id;
        $this->user = $user;
        $this->mailSubject = $mailSubject;
        $this->title = $title;
        $this->message = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        $data = [
            'user' => $this->user,
            'title' => $this->title,
            'message' => $this->message,
        ];

        return new Content(
            markdown: 'mail.royalty.rwbook.success',
            with: $data,
        );
    }

    public function attachments(): array
    {
        $royaltyOutput = MacroOutput::where('batch_id', $this->batch_id)
            ->where('file_type_id', MacroFileTypeEnum::Royalty()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::RoyaltyDefault()->value)
            ->first();

        $royaltyAttachment = Attachment::fromStorageDisk(config('filesystems.upload_disk'), $royaltyOutput->file_path)
            ->as($royaltyOutput->file_name)
            ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return [
            $royaltyAttachment,
        ];
    }
}
