<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { MessageSquare, X, Send, AlertTriangle, CreditCard, Lightbulb, HelpCircle, ArrowLeft, Upload, CheckCircle } from 'lucide-vue-next';
import { useOmniChat } from '@/composables/useOmniChat';

const {
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
} = useOmniChat();

const reportName = ref('');
const reportDescription = ref('');
const reportFile = ref<File | null>(null);
const reportPreview = ref<string | null>(null);
const reportFileInput = ref<HTMLInputElement | null>(null);

function handleReportFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    reportFile.value = file;
    if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => { reportPreview.value = ev.target?.result as string; };
        reader.readAsDataURL(file);
    } else {
        reportPreview.value = null;
    }
}

function clearReportFile() {
    reportFile.value = null;
    reportPreview.value = null;
    if (reportFileInput.value) reportFileInput.value.value = '';
}

function openReportForm() {
    showReportForm.value = true;
    reportName.value = '';
    reportDescription.value = '';
    clearReportFile();
}

function closeReportForm() {
    showReportForm.value = false;
    reportName.value = '';
    reportDescription.value = '';
    clearReportFile();
}

async function handleSubmitReport() {
    if (!reportName.value.trim() || !reportDescription.value.trim()) return;
    await submitReport(reportName.value.trim(), reportDescription.value.trim(), reportFile.value);
    reportName.value = '';
    reportDescription.value = '';
    clearReportFile();
}

const templateStarters = [
    { label: 'Report an Issue', body: "I'd like to report an issue.", icon: AlertTriangle, color: 'text-amber-500' },
    { label: 'Billing & Subscription', body: 'I have a question about billing or my subscription.', icon: CreditCard, color: 'text-blue-500' },
    { label: 'Feature Request', body: "I'd like to suggest a feature.", icon: Lightbulb, color: 'text-yellow-500' },
    { label: 'General Inquiry', body: 'I have a general question.', icon: HelpCircle, color: 'text-teal-500' },
];

const messagesContainer = ref<HTMLDivElement | null>(null);

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

watch(messages, () => {
    scrollToBottom();
}, { deep: true });

watch(isOpen, (open) => {
    if (open) scrollToBottom();
});

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
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
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Chat Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="mb-4 flex h-[500px] w-[380px] flex-col overflow-hidden rounded-2xl border bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <!-- Header -->
                <div class="flex items-center justify-between bg-teal-600 px-4 py-3 text-white">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="size-5" />
                        <span class="font-semibold">OmniChat</span>
                    </div>
                    <button
                        @click="toggleChat"
                        class="rounded-full p-1 transition-colors hover:bg-teal-700"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- Messages -->
                <div
                    ref="messagesContainer"
                    class="flex-1 overflow-y-auto px-4 py-3 space-y-1"
                >
                    <!-- Report Issue Form -->
                    <div
                        v-if="showReportForm && messages.length === 0 && isLoaded"
                        class="flex h-full flex-col px-2 py-3"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <button @click="closeReportForm" class="rounded-lg p-1 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800">
                                <ArrowLeft class="size-4 text-muted-foreground" />
                            </button>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Report an Issue</h3>
                        </div>

                        <div class="flex flex-1 flex-col gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Issue Name <span class="text-red-500">*</span></label>
                                <input
                                    v-model="reportName"
                                    type="text"
                                    placeholder="Brief title of the issue"
                                    class="w-full rounded-lg border bg-transparent px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-teal-500 dark:border-gray-700"
                                />
                            </div>

                            <div class="flex-1">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Description <span class="text-red-500">*</span></label>
                                <textarea
                                    v-model="reportDescription"
                                    placeholder="Describe the issue in detail..."
                                    rows="4"
                                    class="w-full resize-none rounded-lg border bg-transparent px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-teal-500 dark:border-gray-700"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Screenshot (optional)</label>
                                <div v-if="!reportPreview" class="relative">
                                    <input
                                        ref="reportFileInput"
                                        type="file"
                                        accept="image/*"
                                        @change="handleReportFile"
                                        class="hidden"
                                    />
                                    <button
                                        @click="reportFileInput?.click()"
                                        type="button"
                                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 px-3 py-3 text-xs text-muted-foreground transition-colors hover:border-teal-400 hover:bg-teal-50 dark:border-gray-600 dark:hover:border-teal-600 dark:hover:bg-teal-900/20"
                                    >
                                        <Upload class="size-4" />
                                        <span>Click to upload image</span>
                                    </button>
                                </div>
                                <div v-else class="relative">
                                    <img :src="reportPreview" alt="Preview" class="max-h-28 w-full rounded-lg border object-cover dark:border-gray-700" />
                                    <button
                                        @click="clearReportFile"
                                        class="absolute right-1 top-1 rounded-full bg-black/60 p-0.5 text-white transition-colors hover:bg-black/80"
                                    >
                                        <X class="size-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    @click="closeReportForm"
                                    class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                                >
                                    Back
                                </button>
                                <button
                                    @click="handleSubmitReport"
                                    :disabled="sending || !reportName.trim() || !reportDescription.trim()"
                                    class="flex-1 rounded-lg bg-teal-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ sending ? 'Sending...' : 'Submit Report' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Template starters (empty state) -->
                    <div
                        v-if="!showReportForm && messages.length === 0 && isLoaded"
                        class="flex h-full flex-col items-center justify-center px-2"
                    >
                        <MessageSquare class="mb-2 size-8 text-gray-300 dark:text-gray-600" />
                        <p class="text-sm font-medium text-muted-foreground">How can we help?</p>
                        <p class="mb-4 mt-1 text-xs text-muted-foreground">Choose a topic or type a message</p>
                        <div class="grid w-full grid-cols-2 gap-2">
                            <button
                                v-for="starter in templateStarters"
                                :key="starter.label"
                                @click="starter.label === 'Report an Issue' ? openReportForm() : sendTemplateMessage(starter.label, starter.body)"
                                :disabled="sending"
                                class="flex flex-col items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-3 text-center transition-colors hover:border-teal-400 hover:bg-teal-50 disabled:opacity-50 dark:border-gray-700 dark:hover:border-teal-600 dark:hover:bg-teal-900/20"
                            >
                                <component :is="starter.icon" :class="['size-5', starter.color]" />
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ starter.label }}</span>
                            </button>
                        </div>
                    </div>

                    <template v-for="(msg, index) in messages" :key="msg.id">
                        <!-- Date separator -->
                        <div v-if="shouldShowDate(index)" class="flex items-center justify-center py-2">
                            <span class="rounded-full bg-gray-100 px-3 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                {{ formatDate(msg.created_at) }}
                            </span>
                        </div>

                        <!-- Message bubble -->
                        <div
                            :class="[
                                'flex',
                                msg.sender_type === 'user' ? 'justify-end' : 'justify-start',
                            ]"
                        >
                            <div
                                :class="[
                                    'max-w-[80%] rounded-2xl px-3.5 py-2 text-sm',
                                    msg.sender_type === 'user'
                                        ? 'bg-teal-600 text-white rounded-br-md'
                                        : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100 rounded-bl-md',
                                ]"
                            >
                                <div v-if="msg.sender_type === 'admin'" class="mb-0.5 text-xs font-medium text-teal-600 dark:text-teal-400">
                                    Support
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
                                        class="max-h-40 rounded-lg border border-white/20 object-cover"
                                    />
                                </a>
                                <div
                                    :class="[
                                        'mt-1 text-right text-[10px]',
                                        msg.sender_type === 'user' ? 'text-teal-200' : 'text-gray-400 dark:text-gray-500',
                                    ]"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Resolved notice with Agree button -->
                <div
                    v-if="conversation && conversation.status === 'resolved'"
                    class="border-t bg-green-50 px-4 py-3 dark:bg-green-900/20"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-green-700 dark:text-green-400">This conversation has been resolved.</p>
                        <button
                            @click="acknowledgeResolve"
                            class="flex shrink-0 items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-green-700"
                        >
                            <CheckCircle class="size-3.5" />
                            Agree
                        </button>
                    </div>
                </div>

                <!-- Closed/other status notice -->
                <div
                    v-else-if="conversation && conversation.status !== 'open'"
                    class="border-t bg-gray-50 px-4 py-2 text-center text-xs text-muted-foreground dark:bg-gray-800/50"
                >
                    This conversation is {{ conversation.status }}. Send a message to reopen it.
                </div>

                <!-- Input -->
                <div v-if="!conversation || conversation.status !== 'resolved'" class="border-t p-3 dark:border-gray-700">
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="newMessage"
                            @keydown="handleKeydown"
                            placeholder="Type a message..."
                            rows="1"
                            class="flex-1 resize-none rounded-xl border bg-transparent px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-teal-500 dark:border-gray-700"
                            :disabled="sending"
                        />
                        <button
                            @click="sendMessage()"
                            :disabled="sending || !newMessage.trim()"
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white transition-colors hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Send class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Chat Bubble Button -->
        <button
            @click="toggleChat"
            class="relative flex size-14 items-center justify-center rounded-full bg-teal-600 text-white shadow-lg transition-all hover:bg-teal-700 hover:shadow-xl hover:scale-105 active:scale-95"
        >
            <MessageSquare v-if="!isOpen" class="size-6" />
            <X v-else class="size-6" />

            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0 && !isOpen"
                class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>
    </div>
</template>
