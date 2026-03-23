<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: 'Create', href: '/admin/users/create' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    mark_verified: true,
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <Head title="Create User" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Create User</h1>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">Password</Label>
                            <Input id="password" v-model="form.password" type="password" />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="mark_verified"
                            v-model="form.mark_verified"
                            type="checkbox"
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                        <Label for="mark_verified" class="cursor-pointer">Mark as verified</Label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" as-child>
                            <Link href="/admin/users">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            Create User
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
