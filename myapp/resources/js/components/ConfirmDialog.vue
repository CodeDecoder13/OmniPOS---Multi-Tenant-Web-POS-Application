<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

/** One confirm dialog for destructive or hard-to-undo actions. Name the action on the button. */
withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
        loading?: boolean;
    }>(),
    { confirmLabel: 'Confirm', cancelLabel: 'Cancel', destructive: true },
);

const open = defineModel<boolean>('open', { default: false });
defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="loading" @click="open = false">{{ cancelLabel }}</Button>
                <Button :variant="destructive ? 'destructive' : 'default'" :disabled="loading" @click="$emit('confirm')">
                    {{ loading ? '...' : confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
