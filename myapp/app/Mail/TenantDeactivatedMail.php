<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantDeactivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $ownerName;
    public string $tenantName;

    public function __construct(Tenant $tenant, User $owner)
    {
        $this->ownerName = $owner->name;
        $this->tenantName = $tenant->name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Organization \"{$this->tenantName}\" Has Been Deactivated",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-deactivated',
        );
    }
}
