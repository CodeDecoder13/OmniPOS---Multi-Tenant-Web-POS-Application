<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { usePosShift } from '@/composables/usePosShift';
import type { Shift, ShiftSummary } from '@/types/models';

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ 'shift-ended': [payload: { shift: Shift; summary: ShiftSummary }] }>();

const { closeShift } = usePosShift();

const endingCash = ref<number>(0);
const notes = ref('');
const loading = ref(false);
const error = ref('');

async function handleEnd() {
    loading.value = true;
    error.value = '';

    const result = await closeShift(endingCash.value, notes.value);

    if (result.success && result.shift && result.summary) {
        open.value = false;
        emit('shift-ended', { shift: result.shift, summary: result.summary });
        endingCash.value = 0;
        notes.value = '';
    } else {
        error.value = result.message ?? 'Failed to end shift.';
    }

    loading.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>End Shift</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <p class="text-sm text-muted-foreground">
                    Count the cash in your drawer and enter the total below.
                </p>

                <div class="space-y-2">
                    <Label for="ending-cash">Ending Cash</Label>
                    <Input
                        id="ending-cash"
                        v-model.number="endingCash"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="text-lg h-12 text-center font-bold"
                        @keyup.enter="handleEnd"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="shift-notes">Notes (optional)</Label>
                    <Textarea
                        id="shift-notes"
                        v-model="notes"
                        placeholder="Any notes about this shift..."
                        rows="2"
                        class="resize-none text-sm"
                    />
                </div>

                <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="open = false" :disabled="loading">Cancel</Button>
                <Button variant="destructive" @click="handleEnd" :disabled="loading">
                    {{ loading ? 'Closing...' : 'End Shift' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
