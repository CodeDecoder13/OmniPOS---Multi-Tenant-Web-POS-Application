<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { ChatConversation, ChatMessage } from '@/types/models';
import { MessageSquare, Send, X, CheckCircle, Search } from 'lucide-vue-next';
import { ref, watch, nextTick, onMounted } from 'vue';
import { useAdminChat } from '@/composables/useAdminChat';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Chat', href: '/admin/chat' },
];

const {
    conversations,
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
} = useAdminChat();

const statusFilter = ref('');
const search = ref('');
const messagesContainer = ref<HTMLDivElement | null>(null);
let debounceTimer: ReturnType<typeof setTimeout>;

const statusTabs = [
    { label: 'All', value: '' },
    { label: 'Open', value: 'open' },
    { label: 'Closed', value: 'closed' },
    { label: 'Resolved', value: 'resolved' },
];

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

watch(messages, () => scrollToBottom(), { deep: true });

function applyFilters() {
    const filters = {
        status: statusFilter.value || undefined,
        search: search.value || undefined,
    };
    fetchConversations(filters);
    stopAllPolling();
    startListPolling(filters);
}

watch(statusFilter, () => applyFilters());
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 300);
});

function handleSelectConversation(conv: ChatConversation) {
    selectConversation(conv);
    nextTick(() => scrollToBottom());
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendReply();
    }
}

function formatTime(dateStr: string): string {
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    const today = new Date();
    if (d.toDateString() === today.toDateString()) return 'Today';
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function shouldShowDate(index: number): boolean {
    if (index === 0) return true;
    const curr = new Date(messages.value[index].created_at).toDateString();
    const prev = new Date(messages.value[index - 1].created_at).toDateString();
    return curr !== prev;
}

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now.getTime() - d.getTime()) / 1000);
    if (diff < 60) return 'now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    return `${Math.floor(diff / 86400)}d`;
}

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'open': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'closed': return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
        case 'resolved': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-600';
    }
}

onMounted(() => {
    fetchConversations();
    startListPolling();
});
</script>

<template>
    <Head title="Chat" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-8rem)] flex-col gap-0 p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Chat Support</h1>
            </div>

            <div class="grid flex-1 overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900 lg:grid-cols-12">
                <!-- Left panel: Conversation list -->
                <div class="flex flex-col border-r dark:border-gray-800 lg:col-span-4">
                    <!-- Search -->
                    <div class="border-b p-3 dark:border-gray-800">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search users or tenants..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- Status tabs -->
                    <div class="flex border-b px-3 dark:border-gray-800">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            @click="statusFilter = tab.value"
                            :class="[
                                'border-b-2 px-3 py-2 text-xs font-medium transition-colors',
                                statusFilter === tab.value
                                    ? 'border-teal-600 text-teal-600'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Conversation list -->
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-for="conv in conversations"
                            :key="conv.id"
                            @click="handleSelectConversation(conv)"
                            :class="[
                                'cursor-pointer border-b px-4 py-3 transition-colors dark:border-gray-800',
                                activeConversation?.id === conv.id
                                    ? 'bg-teal-50 dark:bg-teal-900/20'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800/50',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium">{{ conv.user?.name ?? 'Unknown' }}</span>
                                        <span
                                            v-if="(conv.unread_count ?? 0) > 0"
                                            class="flex size-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-[10px] font-bold text-white"
                                        >
                                            {{ conv.unread_count }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate text-xs text-muted-foreground">
                                        <span class="truncate">{{ conv.tenant?.name ?? 'No tenant' }}</span>
                                        <span
                                            v-if="conv.subject"
                                            class="inline-flex shrink-0 rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-medium text-teal-700 dark:bg-teal-900/30 dark:text-teal-400"
                                        >
                                            {{ conv.subject }}
                                        </span>
                                    </div>
                                    <div class="mt-1 truncate text-xs text-muted-foreground">
                                        {{ conv.last_message?.message ?? 'No messages' }}
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <span class="text-[10px] text-muted-foreground">{{ timeAgo(conv.last_message_at) }}</span>
                                    <span :class="['inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-medium', statusBadgeClass(conv.status)]">
                                        {{ conv.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="conversations.length === 0 && !loading"
                            class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground"
                        >
                            <MessageSquare class="mb-2 size-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm">No conversations found</p>
                        </div>
                    </div>
                </div>

                <!-- Right panel: Messages -->
                <div class="flex flex-col lg:col-span-8">
                    <template v-if="activeConversation">
                        <!-- Chat header -->
                        <div class="flex items-center justify-between border-b px-4 py-3 dark:border-gray-800">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ activeConversation.user?.name }}</span>
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', statusBadgeClass(activeConversation.status)]">
                                        {{ activeConversation.status }}
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ activeConversation.user?.email }} &middot; {{ activeConversation.tenant?.name }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="activeConversation.status === 'open'"
                                    size="sm"
                                    variant="outline"
                                    @click="resolveConversation"
                                >
                                    <CheckCircle class="mr-1 size-3.5" />
                                    Resolve
                                </Button>
                                <Button
                                    v-if="activeConversation.status === 'open'"
                                    size="sm"
                                    variant="outline"
                                    @click="closeConversation"
                                >
                                    <X class="mr-1 size-3.5" />
                                    Close
                                </Button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-3 space-y-1">
                            <template v-for="(msg, index) in messages" :key="msg.id">
                                <div v-if="shouldShowDate(index)" class="flex items-center justify-center py-2">
                                    <span class="rounded-full bg-gray-100 px-3 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                        {{ formatDate(msg.created_at) }}
                                    </span>
                                </div>

                                <div :class="['flex', msg.sender_type === 'admin' ? 'justify-end' : 'justify-start']">
                                    <div
                                        :class="[
                                            'max-w-[70%] rounded-2xl px-3.5 py-2 text-sm',
                                            msg.sender_type === 'admin'
                                                ? 'bg-teal-600 text-white rounded-br-md'
                                                : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100 rounded-bl-md',
                                        ]"
                                    >
                                        <div v-if="msg.sender_type === 'user'" class="mb-0.5 text-xs font-medium text-teal-600 dark:text-teal-400">
                                            {{ activeConversation.user?.name }}
                                        </div>
                                        <p class="whitespace-pre-wrap break-words">{{ msg.message }}</p>
                                        <a
                                            v-if="msg.attachment_url"
                                            :href="msg.attachment_url"
                                            target="_blank"
                                            class="mt-1.5 block"
                                        >
                                            <img
                                                :src="msg.attachment_url"
                                                alt="Attachment"
                                                class="max-h-48 rounded-lg border border-white/20 object-cover transition-opacity hover:opacity-90"
                                            />
                                        </a>
                                        <div
                                            :class="[
                                                'mt-1 text-right text-[10px]',
                                                msg.sender_type === 'admin' ? 'text-teal-200' : 'text-gray-400 dark:text-gray-500',
                                            ]"
                                        >
                                            {{ formatTime(msg.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="messages.length === 0" class="flex h-full items-center justify-center text-muted-foreground">
                                <p class="text-sm">No messages in this conversation</p>
                            </div>
                        </div>

                        <!-- Reply input -->
                        <div class="border-t p-3 dark:border-gray-800">
                            <div class="flex items-end gap-2">
                                <textarea
                                    v-model="newMessage"
                                    @keydown="handleKeydown"
                                    placeholder="Type a reply..."
                                    rows="2"
                                    class="flex-1 resize-none rounded-xl border bg-transparent px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-teal-500 dark:border-gray-700"
                                    :disabled="sending"
                                />
                                <Button
                                    @click="sendReply"
                                    :disabled="sending || !newMessage.trim()"
                                    class="bg-teal-600 hover:bg-teal-700"
                                >
                                    <Send class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div v-else class="flex flex-1 flex-col items-center justify-center text-muted-foreground">
                        <MessageSquare class="mb-3 size-12 text-gray-300 dark:text-gray-600" />
                        <p class="font-medium">Select a conversation</p>
                        <p class="mt-1 text-sm">Choose a conversation from the list to start replying</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
