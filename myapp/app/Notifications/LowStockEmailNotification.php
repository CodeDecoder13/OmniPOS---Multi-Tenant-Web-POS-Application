<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $productName,
        private string $branchName,
        private int $currentStock,
        private int $threshold,
        private string $tenantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->productName} — {$this->tenantName}")
            ->greeting("Low Stock Alert")
            ->line("**{$this->productName}** at **{$this->branchName}** is running low on stock.")
            ->line("**Current Stock:** {$this->currentStock}")
            ->line("**Threshold:** {$this->threshold}")
            ->line('Please restock this item as soon as possible.')
            ->action('View Inventory', url('/dashboard'))
            ->salutation('— ' . config('app.name'));
    }
}
