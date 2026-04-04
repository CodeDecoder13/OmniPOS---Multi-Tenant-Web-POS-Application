import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useTenant } from '@/composables/useTenant';
import type { ChatConversation, ChatMessage } from '@/types/models';

export function useOmniChat() {
    const { tenantUrl } = useTenant();

    const isOpen = ref(false);
    const isLoaded = ref(false);
    const conversation = ref<ChatConversation | null>(null);
    const messages = ref<ChatMessage[]>([]);
    const unreadCount = ref(0);
    const sending = ref(false);
    const newMessage = ref('');
    const showReportForm = ref(false);

    let openPollTimer: ReturnType<typeof setInterval> | null = null;
    let minimizedPollTimer: ReturnType<typeof setInterval> | null = null;

    function getXsrfToken(): string {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    async function fetchApi(path: string, options: RequestInit = {}) {
        const url = tenantUrl(path);
        const res = await fetch(url, {
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

    async function loadInitial() {
        try {
            const data = await fetchApi('chat');
            conversation.value = data.conversation;
            messages.value = data.messages;
            unreadCount.value = data.unread_count;
            isLoaded.value = true;
        } catch {
            // Silently fail - chat is optional
        }
    }

    async function pollMessages() {
        if (!conversation.value) return;
        const lastId = messages.value.length > 0 ? messages.value[messages.value.length - 1].id : 0;
        try {
            const data = await fetchApi(`chat/poll?after_id=${lastId}`);
            if (data.messages && data.messages.length > 0) {
                messages.value.push(...data.messages);
            }
            if (data.conversation) {
                conversation.value = data.conversation;
            }
            unreadCount.value = data.unread_count;
        } catch {
            // Silent fail
        }
    }

    async function pollUnreadOnly() {
        try {
            const data = await fetchApi('chat/poll?after_id=0');
            unreadCount.value = data.unread_count;
        } catch {
            // Silent fail
        }
    }

    function startOpenPolling() {
        stopAllPolling();
        openPollTimer = setInterval(pollMessages, 3000);
    }

    function startMinimizedPolling() {
        stopAllPolling();
        minimizedPollTimer = setInterval(pollUnreadOnly, 30000);
    }

    function stopAllPolling() {
        if (openPollTimer) { clearInterval(openPollTimer); openPollTimer = null; }
        if (minimizedPollTimer) { clearInterval(minimizedPollTimer); minimizedPollTimer = null; }
    }

    async function toggleChat() {
        isOpen.value = !isOpen.value;
        if (isOpen.value) {
            await loadInitial();
            startOpenPolling();
            if (unreadCount.value > 0) {
                await markAsRead();
            }
        } else {
            startMinimizedPolling();
        }
    }

    async function sendMessage(subject?: string) {
        const text = newMessage.value.trim();
        if (!text || sending.value) return;

        sending.value = true;
        try {
            const payload: Record<string, string> = { message: text };
            if (subject) payload.subject = subject;

            const data = await fetchApi('chat/messages', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            messages.value.push(data.message);
            conversation.value = data.conversation;
            newMessage.value = '';
            await nextTick();
        } catch {
            // Silent fail
        } finally {
            sending.value = false;
        }
    }

    async function sendTemplateMessage(label: string, body: string) {
        newMessage.value = body;
        await sendMessage(label);
    }

    async function submitReport(name: string, description: string, file?: File | null) {
        if (sending.value) return;

        sending.value = true;
        try {
            const formattedMessage = `📋 Issue Report\nIssue: ${name}\nDescription: ${description}`;

            const formData = new FormData();
            formData.append('message', formattedMessage);
            formData.append('subject', 'Report an Issue');
            if (file) {
                formData.append('attachment', file);
            }

            const url = tenantUrl('chat/messages');
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
                method: 'POST',
                body: formData,
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            messages.value.push(data.message);
            conversation.value = data.conversation;
            showReportForm.value = false;

            // Poll immediately to pick up the auto-reply
            await pollMessages();
            await nextTick();
        } catch {
            // Silent fail
        } finally {
            sending.value = false;
        }
    }

    async function markAsRead() {
        try {
            await fetchApi('chat/read', { method: 'POST' });
            unreadCount.value = 0;
        } catch {
            // Silent fail
        }
    }

    async function acknowledgeResolve() {
        try {
            await fetchApi('chat/acknowledge-resolve', { method: 'POST' });
            // Reset state immediately so resolved bar disappears
            conversation.value = null;
            messages.value = [];
            unreadCount.value = 0;
            showReportForm.value = false;
            // Reload to get the new open conversation
            await loadInitial();
        } catch {
            // Silent fail
        }
    }

    onMounted(() => {
        // Start minimized polling for unread badge
        pollUnreadOnly();
        startMinimizedPolling();
    });

    onUnmounted(() => {
        stopAllPolling();
    });

    return {
        isOpen,
        isLoaded,
        conversation,
        messages,
        unreadCount,
        sending,
        newMessage,
        showReportForm,
        toggleChat,
        sendMessage,
        sendTemplateMessage,
        submitReport,
        markAsRead,
        acknowledgeResolve,
    };
}
