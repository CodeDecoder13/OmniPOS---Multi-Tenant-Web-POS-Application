<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Verification Code - ' . config('app.name'))
            ->greeting("Hello {$notifiable->name}!")
            ->line('Use the following code to verify your email address:')
            ->line("# {$this->code}")
            ->line('This code will expire in **15 minutes**.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Regards, ' . config('app.name') . ' Team');
    }
}
