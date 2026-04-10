<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftEndedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $ownerName;
    public string $tenantName;
    public string $operatorName;
    public string $branchName;
    public string $openedAt;
    public string $closedAt;
    public string $hoursRendered;
    public float $totalSales;
    public int $totalOrders;
    public float $avgOrderValue;
    public int $voidedCount;
    public float $startingCash;
    public float $expectedCash;
    public float $actualCash;
    public float $cashDifference;
    public array $paymentBreakdown;
    public array $productsSold;

    public function __construct(array $data)
    {
        $this->ownerName = $data['ownerName'];
        $this->tenantName = $data['tenantName'];
        $this->operatorName = $data['operatorName'];
        $this->branchName = $data['branchName'];
        $this->openedAt = $data['openedAt'];
        $this->closedAt = $data['closedAt'];
        $this->hoursRendered = $data['hoursRendered'];
        $this->totalSales = $data['totalSales'];
        $this->totalOrders = $data['totalOrders'];
        $this->avgOrderValue = $data['avgOrderValue'];
        $this->voidedCount = $data['voidedCount'];
        $this->startingCash = $data['startingCash'];
        $this->expectedCash = $data['expectedCash'];
        $this->actualCash = $data['actualCash'];
        $this->cashDifference = $data['cashDifference'];
        $this->paymentBreakdown = $data['paymentBreakdown'];
        $this->productsSold = $data['productsSold'];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Shift Ended — {$this->operatorName} at {$this->branchName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shift-ended',
        );
    }
}
