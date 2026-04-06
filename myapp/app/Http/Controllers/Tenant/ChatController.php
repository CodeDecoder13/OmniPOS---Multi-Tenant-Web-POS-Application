<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Central\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function index(Request $request, string $tenantSlug): JsonResponse
    {
        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant');

        $conversation = $this->chatService->findConversation($user, $tenant);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $conversation ? $this->chatService->getMessages($conversation) : [],
            'unread_count' => $conversation ? $this->chatService->getUnreadCountForUser($conversation) : 0,
        ]);
    }

    public function poll(Request $request, string $tenantSlug): JsonResponse
    {
        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant');

        $conversation = $this->chatService->findConversation($user, $tenant);

        if (! $conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
                'unread_count' => 0,
            ]);
        }

        $afterId = (int) $request->query('after_id', 0);
        $messages = $afterId
            ? $this->chatService->getNewMessages($conversation, $afterId)
            : collect();

        $unreadCount = $this->chatService->getUnreadCountForUser($conversation);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'unread_count' => $unreadCount,
        ]);
    }

    public function sendMessage(Request $request, string $tenantSlug): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attachment' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant');

        $conversation = $this->chatService->getOrCreateConversation($user, $tenant);
        $message = $this->chatService->sendUserMessage(
            $conversation,
            $user,
            $request->input('message'),
            $request->input('subject'),
            $request->file('attachment'),
        );

        return response()->json([
            'message' => $message,
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function markRead(Request $request, string $tenantSlug): JsonResponse
    {
        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant');

        $conversation = $this->chatService->findConversation($user, $tenant);

        if (! $conversation) {
            return response()->json(['success' => true]);
        }

        $this->chatService->markAdminMessagesRead($conversation);

        return response()->json(['success' => true]);
    }

    public function acknowledgeResolve(Request $request, string $tenantSlug): JsonResponse
    {
        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant');

        $conversation = $this->chatService->findConversation($user, $tenant);

        if ($conversation && $conversation->status === \App\Enums\ChatStatus::Resolved) {
            $this->chatService->acknowledgeResolve($conversation);
        }

        return response()->json([
            'conversation' => null,
            'messages' => [],
        ]);
    }
}
