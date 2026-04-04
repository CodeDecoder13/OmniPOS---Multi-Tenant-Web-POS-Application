import { ref, onUnmounted } from 'vue';
import type { ChatConversation, ChatMessage } from '@/types/models';

export function useAdminChat() {
    const conversations = ref<ChatConversation[]>([]);
    const totalPages = ref(1);
    const activeConversation = ref<ChatConversation | null>(null);
    const messages = ref<ChatMessage[]>([]);
    const loading = ref(false);
    const sending = ref(false);
    const newMessage = ref('');

    let messagePollTimer: ReturnType<typeof setInterval> | null = null;
    let listPollTimer: ReturnType<typeof setInterval> | null = null;

    function getXsrfToken(): string {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    async function fetchApi(path: string, options: RequestInit = {}) {
        const res = await fetch(`/admin/${path}`, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
                ...((options.headers as Record<string, string>) || {}),
            },
            credentials: 'same-origin',
            ...options,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }

    async function fetchConversations(filters: { status?: string; search?: string } = {}) {
        loading.value = true;
        try {
            const params = new URLSearchParams();
            if (filters.status) params.set('status', filters.status);
            if (filters.search) params.set('search', filters.search);
            const qs = params.toString();

            const data = await fetchApi(`chat/conversations${qs ? '?' + qs : ''}`);
            conversations.value = data.data;
            totalPages.value = data.last_page;
        } catch {
            // Silent fail
        } finally {
            loading.value = false;
        }
    }

    async function selectConversation(conv: ChatConversation) {
        activeConversation.value = conv;
        loading.value = true;
        stopMessagePolling();

        try {
            const data = await fetchApi(`chat/conversations/${conv.id}/messages`);
            messages.value = data.messages;
            activeConversation.value = data.conversation;

            // Mark as read
            await fetchApi(`chat/conversations/${conv.id}/read`, { method: 'POST' });
            conv.unread_count = 0;

            startMessagePolling();
        } catch {
            // Silent fail
        } finally {
            loading.value = false;
        }
    }

    async function pollMessages() {
        if (!activeConversation.value) return;
        const lastId = messages.value.length > 0 ? messages.value[messages.value.length - 1].id : 0;
        try {
            const data = await fetchApi(`chat/conversations/${activeConversation.value.id}/poll?after_id=${lastId}`);
            if (data.messages && data.messages.length > 0) {
                messages.value.push(...data.messages);
                // Mark new messages as read
                await fetchApi(`chat/conversations/${activeConversation.value.id}/read`, { method: 'POST' });
            }
        } catch {
            // Silent fail
        }
    }

    async function sendReply() {
        const text = newMessage.value.trim();
        if (!text || sending.value || !activeConversation.value) return;

        sending.value = true;
        try {
            const data = await fetchApi(`chat/conversations/${activeConversation.value.id}/messages`, {
                method: 'POST',
                body: JSON.stringify({ message: text }),
            });
            messages.value.push(data.message);
            newMessage.value = '';
        } catch {
            // Silent fail
        } finally {
            sending.value = false;
        }
    }

    async function closeConversation() {
        if (!activeConversation.value) return;
        try {
            const data = await fetchApi(`chat/conversations/${activeConversation.value.id}/close`, { method: 'POST' });
            activeConversation.value = data.conversation;
            // Update in list
            const idx = conversations.value.findIndex(c => c.id === data.conversation.id);
            if (idx !== -1) conversations.value[idx] = { ...conversations.value[idx], ...data.conversation };
        } catch {
            // Silent fail
        }
    }

    async function resolveConversation() {
        if (!activeConversation.value) return;
        try {
            const data = await fetchApi(`chat/conversations/${activeConversation.value.id}/resolve`, { method: 'POST' });
            activeConversation.value = data.conversation;
            const idx = conversations.value.findIndex(c => c.id === data.conversation.id);
            if (idx !== -1) conversations.value[idx] = { ...conversations.value[idx], ...data.conversation };
        } catch {
            // Silent fail
        }
    }

    function startMessagePolling() {
        stopMessagePolling();
        messagePollTimer = setInterval(pollMessages, 3000);
    }

    function stopMessagePolling() {
        if (messagePollTimer) { clearInterval(messagePollTimer); messagePollTimer = null; }
    }

    function startListPolling(filters: { status?: string; search?: string } = {}) {
        stopListPolling();
        listPollTimer = setInterval(() => fetchConversations(filters), 10000);
    }

    function stopListPolling() {
        if (listPollTimer) { clearInterval(listPollTimer); listPollTimer = null; }
    }

    function stopAllPolling() {
        stopMessagePolling();
        stopListPolling();
    }

    onUnmounted(() => {
        stopAllPolling();
    });

    return {
        conversations,
        totalPages,
        activeConversation,
        messages,
        loading,
        sending,
        newMessage,
        fetchConversations,
        selectConversation,
        sendReply,
        closeConversation,
        resolveConversation,
        startListPolling,
        stopAllPolling,
    };
}
