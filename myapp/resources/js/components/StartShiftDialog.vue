<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePosShift } from '@/composables/usePosShift';
import { usePosAuth } from '@/composables/usePosAuth';

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ 'shift-started': [] }>();

const { operatorUserId } = usePosAuth();
const { openShift } = usePosShift();

const startingCash = ref<number>(0);
const loading = ref(false);
const error = ref('');

async function handleStart() {
    if (!operatorUserId.value) return;
    loading.value = true;
    error.value = '';

    const result = await openShift(operatorUserId.value, startingCash.value);

    if (result.success) {
        open.value = false;
        startingCash.value = 0;
        emit('shift-started');
    } else {
        error.value = result.message ?? 'Failed to start shift.';
    }

    loading.value = false;
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Start Shift</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <p class="text-sm text-muted-foreground">
                    Enter the starting cash in your drawer to begin your shift.
                </p>

                <div class="space-y-2">
                    <Label for="starting-cash">Starting Cash</Label>
                    <Input
                        id="starting-cash"
                        v-model.number="startingCash"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="text-lg h-12 text-center font-bold"
                        @keyup.enter="handleStart"
                    />
                </div>

                <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="open = false" :disabled="loading">Cancel</Button>
                <Button @click="handleStart" :disabled="loading">
                    {{ loading ? 'Starting...' : 'Start Shift' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
