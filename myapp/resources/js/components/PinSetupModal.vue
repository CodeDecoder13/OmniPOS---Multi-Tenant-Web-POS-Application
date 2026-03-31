<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTenant } from '@/composables/useTenant';
import { ref } from 'vue';
import axios from 'axios';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { tenantUrl } = useTenant();

const pin = ref('');
const pinConfirmation = ref('');
const error = ref('');
const processing = ref(false);

async function submit() {
    error.value = '';
    processing.value = true;

    try {
        await axios.post(tenantUrl('pos/pin/set'), {
            pin: pin.value,
            pin_confirmation: pinConfirmation.value,
        });
        pin.value = '';
        pinConfirmation.value = '';
        emit('close');
    } catch (e: any) {
        if (e.response?.data?.errors?.pin) {
            error.value = e.response.data.errors.pin[0];
        } else if (e.response?.data?.message) {
            error.value = e.response.data.message;
        } else {
            error.value = 'Something went wrong. Please try again.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog :open="show" @update:open="emit('close')">
        <DialogContent :show-close-button="true" class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Set Your POS PIN</DialogTitle>
                <DialogDescription>
                    Create a PIN to quickly log in to the point-of-sale terminal. You can change it later in your settings.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="modal-pin">PIN (4-6 digits)</Label>
                    <Input
                        id="modal-pin"
                        v-model="pin"
                        type="password"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="Enter PIN"
                        class="mt-1"
                    />
                </div>

                <div>
                    <Label for="modal-pin-confirm">Confirm PIN</Label>
                    <Input
                        id="modal-pin-confirm"
                        v-model="pinConfirmation"
                        type="password"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="Confirm PIN"
                        class="mt-1"
                    />
                </div>

                <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

                <DialogFooter>
                    <Button type="submit" class="w-full" :disabled="processing">
                        {{ processing ? 'Setting PIN...' : 'Set PIN' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
