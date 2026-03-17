<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Lock, Delete } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { usePosAuth } from '@/composables/usePosAuth';
import { useTenant } from '@/composables/useTenant';

const emit = defineEmits<{
    authenticated: [];
}>();

const { tenant } = useTenant();
const { loginWithPin } = usePosAuth();

const pin = ref('');
const error = ref('');
const loading = ref(false);

const maskedPin = computed(() => '\u25CF'.repeat(pin.value.length));
const canSubmit = computed(() => pin.value.length >= 4 && !loading.value);

function appendDigit(digit: string) {
    if (pin.value.length < 6) {
        pin.value += digit;
        error.value = '';
    }
}

function backspace() {
    pin.value = pin.value.slice(0, -1);
    error.value = '';
}

function clearPin() {
    pin.value = '';
    error.value = '';
}

async function submit() {
    if (!canSubmit.value) return;
    loading.value = true;
    error.value = '';

    const result = await loginWithPin(pin.value);

    if (result.success) {
        emit('authenticated');
    } else {
        error.value = result.message ?? 'Invalid PIN.';
        pin.value = '';
    }

    loading.value = false;
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key >= '0' && e.key <= '9') {
        appendDigit(e.key);
    } else if (e.key === 'Backspace') {
        backspace();
    } else if (e.key === 'Enter') {
        submit();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'clear', '0', 'back'];
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-background/95 backdrop-blur-sm">
        <div class="flex w-full max-w-sm flex-col items-center gap-6 px-4">
            <!-- Header -->
            <div class="flex flex-col items-center gap-2">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                    <Lock class="h-8 w-8 text-primary" />
                </div>
                <h1 class="text-xl font-bold">{{ tenant?.name }}</h1>
                <p class="text-sm text-muted-foreground">Enter your PIN to start</p>
            </div>

            <!-- PIN display -->
            <div class="flex h-14 w-full items-center justify-center rounded-lg border bg-card">
                <span v-if="pin.length > 0" class="text-3xl tracking-[0.5em] font-bold">{{ maskedPin }}</span>
                <span v-else class="text-sm text-muted-foreground">4-6 digit PIN</span>
            </div>

            <!-- Error -->
            <p v-if="error" class="text-sm text-destructive text-center">{{ error }}</p>

            <!-- Keypad -->
            <div class="grid w-full grid-cols-3 gap-2">
                <template v-for="key in keys" :key="key">
                    <button
                        v-if="key === 'clear'"
                        @click="clearPin"
                        class="flex h-14 items-center justify-center rounded-lg border bg-card text-sm font-medium text-muted-foreground transition-colors hover:bg-muted active:bg-muted/80"
                    >
                        Clear
                    </button>
                    <button
                        v-else-if="key === 'back'"
                        @click="backspace"
                        class="flex h-14 items-center justify-center rounded-lg border bg-card transition-colors hover:bg-muted active:bg-muted/80"
                    >
                        <Delete class="h-5 w-5 text-muted-foreground" />
                    </button>
                    <button
                        v-else
                        @click="appendDigit(key)"
                        class="flex h-14 items-center justify-center rounded-lg border bg-card text-xl font-semibold transition-colors hover:bg-muted active:bg-muted/80"
                    >
                        {{ key }}
                    </button>
                </template>
            </div>

            <!-- Submit -->
            <Button
                class="h-12 w-full text-base font-bold"
                :disabled="!canSubmit"
                @click="submit"
            >
                {{ loading ? 'Verifying...' : 'Unlock' }}
            </Button>

            <!-- Help text -->
            <p class="text-xs text-muted-foreground text-center">
                Don't have a PIN? Ask your manager to set one in the Users page.
            </p>
        </div>
    </div>
</template>
