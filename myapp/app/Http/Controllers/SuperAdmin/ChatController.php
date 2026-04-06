<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Central\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/chat/Index');
    }

    public function conversations(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $conversations = $this->chatService->listConversations($filters);

        return response()->json($conversations);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);

        $beforeId = $request->query('before_id') ? (int) $request->query('before_id') : null;
        $messages = $this->chatService->getMessages($conversation, $beforeId);

        return response()->json([
            'messages' => $messages,
            'conversation' => $conversation->load(['user:id,name,email', 'tenant:id,name,slug']),
        ]);
    }

    public function poll(Request $request, int $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);

        $afterId = (int) $request->query('after_id', 0);
        $messages = $afterId
            ? $this->chatService->getNewMessages($conversation, $afterId)
            : collect();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = ChatConversation::findOrFail($id);
        $adminId = $request->user('admin')->id;

        $message = $this->chatService->sendAdminMessage($conversation, $adminId, $request->input('message'));

        return response()->json([
            'message' => $message,
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);
        $this->chatService->markUserMessagesRead($conversation);

        return response()->json(['success' => true]);
    }

    public function close(int $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);
        $conversation = $this->chatService->closeConversation($conversation);

        return response()->json(['conversation' => $conversation]);
    }

    public function resolve(int $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);
        $conversation = $this->chatService->resolveConversation($conversation);

        return response()->json(['conversation' => $conversation]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->chatService->getTotalUnreadForAdmin();

        return response()->json(['unread_count' => $count]);
    }
}
