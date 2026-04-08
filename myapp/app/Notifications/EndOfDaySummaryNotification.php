<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndOfDaySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $stats,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Daily Summary for {$this->stats['tenant_name']} — {$this->stats['date']}")
            ->greeting("End of Day Summary")
            ->line("Here's your daily summary for **{$this->stats['tenant_name']}** on {$this->stats['date']}:")
            ->line("**Orders:** {$this->stats['order_count']}")
            ->line("**Total Revenue:** ₱" . number_format($this->stats['total_revenue'], 2))
            ->line("**Avg Order Value:** ₱" . number_format($this->stats['avg_order_value'], 2))
            ->line("**Voided Orders:** {$this->stats['voided_count']}")
            ->line("**Refunded Orders:** {$this->stats['refunded_count']}")
            ->action('View Dashboard', url('/dashboard'))
            ->salutation('— ' . config('app.name'));
    }
}
