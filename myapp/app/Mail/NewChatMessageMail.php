<?php

namespace App\Mail;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewChatMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $userEmail;
    public string $tenantName;
    public string $messageBody;
    public ?string $chatSubject;

    public function __construct(User $user, ChatConversation $conversation, ChatMessage $message)
    {
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->tenantName = $conversation->tenant->name ?? 'Unknown Tenant';
        $this->messageBody = $message->message;
        $this->chatSubject = $conversation->subject;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Chat Message from {$this->userName} ({$this->tenantName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-chat-message',
        );
    }
}
