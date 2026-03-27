<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTenant } from '@/composables/useTenant';
import { ref, watch } from 'vue';

const props = defineProps<{
    userEmail: string;
    tenantName: string;
}>();

const { tenantUrl } = useTenant();

const autoGenerateCode = ref(true);

const form = useForm({
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

watch(() => form.name, (newName) => {
    if (autoGenerateCode.value) {
        form.code = generateCode(newName);
    }
});

function onCodeInput() {
    autoGenerateCode.value = false;
}

function submit() {
    form.post(tenantUrl('setup'));
}
</script>

<template>
    <Head title="Setup Your First Branch" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 dark:bg-gray-950">
        <div class="w-full max-w-lg">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-2xl font-bold text-primary-foreground">
                    O
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Welcome to OmniPOS</h1>
                <p class="mt-1 text-muted-foreground">Set up your first branch to get started</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Branch Details</CardTitle>
                    <CardDescription>
                        Every store needs at least one branch. You can add more later.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="name">Branch Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Main Branch"
                                class="mt-1"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <Label for="code">Branch Code</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="MAIN"
                                maxlength="20"
                                class="mt-1"
                                @input="onCodeInput"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-sm text-red-500">{{ form.errors.code }}</p>
                            <p v-else class="mt-1 text-xs text-muted-foreground">Unique identifier for this branch (auto-generated)</p>
                        </div>

                        <div>
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="123 Main St"
                                class="mt-1"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="+63 912 345 6789"
                                    maxlength="20"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div>
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="branch@example.com"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ form.processing ? 'Creating Branch...' : 'Create Branch & Continue' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
