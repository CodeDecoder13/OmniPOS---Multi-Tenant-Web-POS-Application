<script setup lang="ts">
import { useFlash } from '@/composables/useFlash';
import { X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { flash } = useFlash();

const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error'>('success');
let timeout: ReturnType<typeof setTimeout>;

watch(
    flash,
    (val) => {
        if (val.success) {
            show(val.success, 'success');
        } else if (val.error) {
            show(val.error, 'error');
        }
    },
    { immediate: true },
);

function show(msg: string, t: 'success' | 'error') {
    message.value = msg;
    type.value = t;
    visible.value = true;
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        visible.value = false;
    }, 5000);
}

function close() {
    visible.value = false;
    clearTimeout(timeout);
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-[-100%] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-100%] opacity-0"
    >
        <div
            v-if="visible"
            class="fixed top-4 right-4 z-50 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
            :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
        >
            <span>{{ message }}</span>
            <button @click="close" class="ml-2 rounded-full p-0.5 hover:bg-white/20">
                <X class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>
