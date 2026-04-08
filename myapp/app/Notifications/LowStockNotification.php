<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $productName,
        private string $branchName,
        private int $currentStock,
        private int $threshold,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_name' => $this->productName,
            'branch_name' => $this->branchName,
            'current_stock' => $this->currentStock,
            'threshold' => $this->threshold,
            'message' => "{$this->productName} is low on stock ({$this->currentStock}) at {$this->branchName}.",
        ];
    }
}
