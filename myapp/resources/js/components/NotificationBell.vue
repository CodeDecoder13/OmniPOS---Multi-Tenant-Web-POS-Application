<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Bell } from 'lucide-vue-next';
import { useTenant } from '@/composables/useTenant';
import axios from 'axios';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';

interface AppNotification {
    id: string;
    data: {
        type: string;
        message: string;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
}

const { tenantUrl } = useTenant();

const notifications = ref<AppNotification[]>([]);
const unreadCount = ref(0);
let pollInterval: ReturnType<typeof setInterval> | null = null;

async function fetchNotifications() {
    try {
        const { data } = await axios.get(tenantUrl('notifications'));
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch {
        // silently fail
    }
}

async function markAllRead() {
    try {
        await axios.post(tenantUrl('notifications/mark-read'));
        notifications.value = notifications.value.map((n) => ({
            ...n,
            read_at: n.read_at ?? new Date().toISOString(),
        }));
        unreadCount.value = 0;
    } catch {
        // silently fail
    }
}

async function markOneRead(id: string) {
    try {
        await axios.post(tenantUrl(`notifications/${id}/read`));
        const n = notifications.value.find((n) => n.id === id);
        if (n) n.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
        // silently fail
    }
}

function timeAgo(dateStr: string): string {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return `${diffHr}h ago`;
    const diffDay = Math.floor(diffHr / 24);
    return `${diffDay}d ago`;
}

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between px-4 py-3">
                <DropdownMenuLabel class="p-0 text-sm font-semibold">Notifications</DropdownMenuLabel>
                <button
                    v-if="unreadCount > 0"
                    class="text-xs text-blue-600 hover:underline dark:text-blue-400"
                    @click.stop="markAllRead"
                >
                    Mark all read
                </button>
            </div>
            <DropdownMenuSeparator class="m-0" />
            <div class="max-h-80 overflow-y-auto">
                <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    No notifications
                </div>
                <button
                    v-for="n in notifications"
                    :key="n.id"
                    class="flex w-full items-start gap-3 border-b px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-muted/50"
                    :class="{ 'bg-blue-50/50 dark:bg-blue-950/20': !n.read_at }"
                    @click.stop="!n.read_at && markOneRead(n.id)"
                >
                    <div
                        class="mt-1 h-2 w-2 shrink-0 rounded-full"
                        :class="n.read_at ? 'bg-transparent' : 'bg-blue-500'"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-snug">{{ n.data.message }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ timeAgo(n.created_at) }}</p>
                    </div>
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
