<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyTimesheetsAdminMail extends Mailable
{
    use Queueable, SerializesModels;
    public $pdfPath;
    public $startOfWeek;

    public function __construct($pdfPath, $startOfWeek)
    {
        $this->pdfPath = $pdfPath;
        $this->startOfWeek = $startOfWeek;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly Timesheets - {$this->startOfWeek->format('d M Y')}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-timesheets-admin',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("Weekly_Timesheets_{$this->startOfWeek->format('Y-m-d')}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
