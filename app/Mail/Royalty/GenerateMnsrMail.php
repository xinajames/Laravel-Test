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

class GenerateMnsrMail extends Mailable
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
            markdown: 'mail.royalty.mnsr.success',
            with: $data,
        );
    }

    public function attachments(): array
    {
        $mnsrOutput = MacroOutput::where('batch_id', $this->batch_id)
            ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedJBMISData()->value)
            ->first();

        $mnsrAttachment = Attachment::fromStorageDisk(config('filesystems.upload_disk'), $mnsrOutput->file_path)
            ->as($mnsrOutput->file_name)
            ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return [
            $mnsrAttachment,
        ];
    }
}
