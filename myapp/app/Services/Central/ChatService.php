<?php

namespace App\Services\Central;

use App\Enums\ChatStatus;
use App\Mail\NewChatMessageMail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class ChatService
{
    // ── Tenant-side methods ──

    public function findConversation(User $user, Tenant $tenant): ?ChatConversation
    {
        return ChatConversation::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [ChatStatus::Open, ChatStatus::Resolved])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();
    }

    public function getOrCreateConversation(User $user, Tenant $tenant): ChatConversation
    {
        $conversation = ChatConversation::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [ChatStatus::Open, ChatStatus::Resolved])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'status' => ChatStatus::Open,
            ]);
        }

        return $conversation;
    }

    public function getMessages(ChatConversation $conversation, ?int $beforeId = null, int $limit = 50): Collection
    {
        $query = $conversation->messages()->orderByDesc('id');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->limit($limit)->get()->reverse()->values();
    }

    public function getNewMessages(ChatConversation $conversation, int $afterId): Collection
    {
        return $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();
    }

    public function sendUserMessage(ChatConversation $conversation, User $user, string $message, ?string $subject = null, ?UploadedFile $attachment = null): ChatMessage
    {
        if ($conversation->status !== ChatStatus::Open) {
            $conversation->update(['status' => ChatStatus::Open]);
        }

        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store(
                "chat/{$user->id}/{$conversation->id}",
                'public',
            );
        }

        $chatMessage = $conversation->messages()->create([
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'message' => $message,
            'attachment_path' => $attachmentPath,
        ]);

        $updateData = ['last_message_at' => now()];

        if ($subject && ! $conversation->subject) {
            $updateData['subject'] = $subject;
        }

        $conversation->update($updateData);

        Mail::to(['rhuzzel.paramio@omnipos.shop', 'boyparamio@gmail.com'])->queue(
            new NewChatMessageMail($user, $conversation, $chatMessage)
        );

        // Auto-reply for issue reports
        if ($subject === 'Report an Issue') {
            $conversation->messages()->create([
                'sender_type' => 'admin',
                'sender_id' => 0,
                'message' => 'Thank you for reporting this issue. Our support team will review it shortly.',
            ]);

            $conversation->update(['last_message_at' => now()]);
        }

        return $chatMessage;
    }

    public function markAdminMessagesRead(ChatConversation $conversation): int
    {
        return $conversation->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function getUnreadCountForUser(ChatConversation $conversation): int
    {
        return $conversation->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->count();
    }

    // ── Admin-side methods ──

    public function listConversations(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ChatConversation::with(['user:id,name,email', 'tenant:id,name,slug', 'lastMessage'])
            ->has('messages')
            ->withCount([
                'messages as unread_count' => function ($q) {
                    $q->where('sender_type', 'user')->where('is_read', false);
                },
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                })->orWhereHas('tenant', function ($tq) use ($search) {
                    $tq->where('name', 'ilike', "%{$search}%");
                });
            });
        }

        return $query->orderByDesc('last_message_at')->paginate($perPage);
    }

    public function sendAdminMessage(ChatConversation $conversation, int $adminId, string $message): ChatMessage
    {
        if ($conversation->status !== ChatStatus::Open) {
            $conversation->update(['status' => ChatStatus::Open]);
        }

        $chatMessage = $conversation->messages()->create([
            'sender_type' => 'admin',
            'sender_id' => $adminId,
            'message' => $message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $chatMessage;
    }

    public function markUserMessagesRead(ChatConversation $conversation): int
    {
        return $conversation->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function closeConversation(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['status' => ChatStatus::Closed]);

        return $conversation->fresh();
    }

    public function resolveConversation(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['status' => ChatStatus::Resolved]);

        // Auto-send resolution message to user
        $conversation->messages()->create([
            'sender_type' => 'admin',
            'sender_id' => 0,
            'message' => 'Your issue has been resolved. If you are satisfied, please click "Agree" below to close this conversation.',
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $conversation->fresh();
    }

    public function acknowledgeResolve(ChatConversation $conversation): void
    {
        $conversation->update(['status' => ChatStatus::Closed]);
    }

    public function getTotalUnreadForAdmin(): int
    {
        return ChatMessage::where('sender_type', 'user')
            ->where('is_read', false)
            ->count();
    }
}
