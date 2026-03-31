<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTenant } from '@/composables/useTenant';
import { ref, watch } from 'vue';

const props = defineProps<{
    step: number;
    userEmail: string;
    tenantName: string;
}>();

const { tenantUrl } = useTenant();

// Step 1: Branch form
const autoGenerateCode = ref(true);

const branchForm = useForm({
    name: props.tenantName,
    code: generateCode(props.tenantName),
    address: '',
    phone: '',
    email: props.userEmail,
});

function generateCode(name: string): string {
    return name
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
        .slice(0, 20);
}

watch(() => branchForm.name, (newName) => {
    if (autoGenerateCode.value) {
        branchForm.code = generateCode(newName);
    }
});

function onCodeInput() {
    autoGenerateCode.value = false;
}

function submitBranch() {
    branchForm.post(tenantUrl('setup'));
}

// Step 2: PIN form
const pinForm = useForm({
    pin: '',
    pin_confirmation: '',
});

function submitPin() {
    pinForm.post(tenantUrl('setup/pin'));
}

function skipPin() {
    router.visit(tenantUrl('dashboard'));
}
</script>

<template>
    <Head :title="step === 1 ? 'Setup Your First Branch' : 'Set Your POS PIN'" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 dark:bg-gray-950">
        <div class="w-full max-w-lg">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-2xl font-bold text-primary-foreground">
                    O
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Welcome to OmniPOS</h1>
                <p class="mt-1 text-muted-foreground">
                    {{ step === 1 ? 'Set up your first branch to get started' : 'Almost done! Set up your POS PIN' }}
                </p>

                <!-- Step indicator -->
                <div class="mt-4 flex items-center justify-center gap-2">
                    <div
                        class="h-2.5 w-2.5 rounded-full transition-colors"
                        :class="step === 1 ? 'bg-primary' : 'bg-primary/30'"
                    />
                    <div
                        class="h-2.5 w-2.5 rounded-full transition-colors"
                        :class="step === 2 ? 'bg-primary' : 'bg-primary/30'"
                    />
                </div>
            </div>

            <!-- Step 1: Branch Creation -->
            <Card v-if="step === 1">
                <CardHeader>
                    <CardTitle>Branch Details</CardTitle>
                    <CardDescription>
                        Every store needs at least one branch. You can add more later.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitBranch" class="space-y-4">
                        <div>
                            <Label for="name">Branch Name</Label>
                            <Input
                                id="name"
                                v-model="branchForm.name"
                                placeholder="Main Branch"
                                class="mt-1"
                            />
                            <p v-if="branchForm.errors.name" class="mt-1 text-sm text-red-500">{{ branchForm.errors.name }}</p>
                        </div>

                        <div>
                            <Label for="code">Branch Code</Label>
                            <Input
                                id="code"
                                v-model="branchForm.code"
                                placeholder="MAIN"
                                maxlength="20"
                                class="mt-1"
                                @input="onCodeInput"
                            />
                            <p v-if="branchForm.errors.code" class="mt-1 text-sm text-red-500">{{ branchForm.errors.code }}</p>
                            <p v-else class="mt-1 text-xs text-muted-foreground">Unique identifier for this branch (auto-generated)</p>
                        </div>

                        <div>
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="branchForm.address"
                                placeholder="123 Main St"
                                class="mt-1"
                            />
                            <p v-if="branchForm.errors.address" class="mt-1 text-sm text-red-500">{{ branchForm.errors.address }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    v-model="branchForm.phone"
                                    placeholder="+63 912 345 6789"
                                    maxlength="20"
                                    class="mt-1"
                                />
                                <p v-if="branchForm.errors.phone" class="mt-1 text-sm text-red-500">{{ branchForm.errors.phone }}</p>
                            </div>

                            <div>
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="branchForm.email"
                                    type="email"
                                    placeholder="branch@example.com"
                                    class="mt-1"
                                />
                                <p v-if="branchForm.errors.email" class="mt-1 text-sm text-red-500">{{ branchForm.errors.email }}</p>
                            </div>
                        </div>

                        <Button type="submit" class="w-full" :disabled="branchForm.processing">
                            {{ branchForm.processing ? 'Creating Branch...' : 'Create Branch & Continue' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Step 2: PIN Setup -->
            <Card v-else-if="step === 2">
                <CardHeader>
                    <CardTitle>Set Your POS PIN</CardTitle>
                    <CardDescription>
                        This PIN lets you quickly log in to the point-of-sale terminal.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitPin" class="space-y-4">
                        <div>
                            <Label for="pin">PIN (4-6 digits)</Label>
                            <Input
                                id="pin"
                                v-model="pinForm.pin"
                                type="password"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="Enter PIN"
                                class="mt-1"
                            />
                            <p v-if="pinForm.errors.pin" class="mt-1 text-sm text-red-500">{{ pinForm.errors.pin }}</p>
                        </div>

                        <div>
                            <Label for="pin_confirmation">Confirm PIN</Label>
                            <Input
                                id="pin_confirmation"
                                v-model="pinForm.pin_confirmation"
                                type="password"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="Confirm PIN"
                                class="mt-1"
                            />
                        </div>

                        <Button type="submit" class="w-full" :disabled="pinForm.processing">
                            {{ pinForm.processing ? 'Setting PIN...' : 'Set PIN & Continue' }}
                        </Button>

                        <button
                            type="button"
                            class="w-full text-center text-sm text-muted-foreground hover:text-foreground transition-colors"
                            @click="skipPin"
                        >
                            Skip for now
                        </button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
