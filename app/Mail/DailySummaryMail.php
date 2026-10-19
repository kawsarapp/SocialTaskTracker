<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenantName;
    public $date;
    public $stats;
    public $staffStats;
    public $pendingLinks;
    public $leaves;
    public $shiftStats;

    /**
     * Create a new message instance.
     */
    public function __construct($tenantName, $date, $stats, $staffStats, $pendingLinks, $leaves = [], $shiftStats = [])
    {
        $this->tenantName = $tenantName;
        $this->date = $date;
        $this->stats = $stats;
        $this->staffStats = $staffStats;
        $this->pendingLinks = $pendingLinks;
        $this->leaves = $leaves;
        $this->shiftStats = $shiftStats;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->tenantName . '] Daily Work Performance Summary - ' . $this->date,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily_summary',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
