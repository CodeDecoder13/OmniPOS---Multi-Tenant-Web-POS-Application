<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftStartedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $ownerName;
    public string $tenantName;
    public string $operatorName;
    public string $branchName;
    public string $openedAt;
    public float $startingCash;

    public function __construct(array $data)
    {
        $this->ownerName = $data['ownerName'];
        $this->tenantName = $data['tenantName'];
        $this->operatorName = $data['operatorName'];
        $this->branchName = $data['branchName'];
        $this->openedAt = $data['openedAt'];
        $this->startingCash = $data['startingCash'];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Shift Started — {$this->operatorName} at {$this->branchName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shift-started',
        );
    }
}
