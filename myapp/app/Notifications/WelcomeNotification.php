<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $storeName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . '!')
            ->greeting("Welcome, {$notifiable->name}!")
            ->line("Your account and store **{$this->storeName}** have been created successfully.")
            ->line('Here\'s what you can do next:')
            ->line('1. Verify your email address (check your inbox)')
            ->line('2. Set up your branches and team members')
            ->line('3. Add your products and start selling')
            ->action('Get Started', url('/dashboard'))
            ->line('If you have any questions, feel free to reach out to our support team.')
            ->salutation('Welcome aboard, ' . config('app.name'));
    }
}
