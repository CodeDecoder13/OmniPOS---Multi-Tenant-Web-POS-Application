<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $ipAddress,
        private string $userAgent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $browser = $this->parseBrowser($this->userAgent);
        $time = now()->format('M d, Y h:i A');

        return (new MailMessage)
            ->subject('New Login to Your Account')
            ->greeting("Hello {$notifiable->name}!")
            ->line('We detected a new login to your account.')
            ->line("**Time:** {$time}")
            ->line("**IP Address:** {$this->ipAddress}")
            ->line("**Browser:** {$browser}")
            ->line('If this was you, no action is needed.')
            ->line('If you did not log in, please secure your account immediately by changing your password.')
            ->action('Go to Dashboard', url('/dashboard'))
            ->salutation('Stay secure, ' . config('app.name'));
    }

    private function parseBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($userAgent, 'Edg')) {
            return 'Microsoft Edge';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            return 'Opera';
        }

        return 'Unknown Browser';
    }
}
