<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class userSummaryEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $TimesheetPdf;

    /**
     * Create a new message instance.
     */
    public function __construct($Pdf)
    {
        $this->TimesheetPdf = $Pdf;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'User Summary Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.allsheet',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        info('attach to email'.now());
        return [
            Attachment::fromData(function () {
                return $this->TimesheetPdf;
            }, 'weeklySummary.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
