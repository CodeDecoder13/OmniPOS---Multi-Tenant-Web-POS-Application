<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Admin Login" />

    <div class="flex min-h-svh items-center justify-center bg-gray-100 dark:bg-gray-950">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-teal-600">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Portal</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">OmniPOS Administration</p>
            </div>

            <div class="rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="admin@omnipos.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                        <Label for="remember" class="text-sm">Remember me</Label>
                    </div>

                    <Button
                        type="submit"
                        class="w-full bg-teal-600 hover:bg-teal-700"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Sign in
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
