<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Mail, User } from 'lucide-vue-next';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import TenantLayout from '@/layouts/TenantLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const showAvatar = computed(() => user.value.avatar && user.value.avatar !== '');
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile settings</h1>

        <SettingsLayout>
            <!-- Profile Header Card -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-4">
                        <Avatar class="h-16 w-16 rounded-full text-lg">
                            <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                            <AvatarFallback class="rounded-full text-lg">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h2 class="text-lg font-semibold">{{ user.name }}</h2>
                            <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Profile Information Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <User class="h-4.5 w-4.5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Profile information</CardTitle>
                            <CardDescription>Update your name and email address</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="ProfileController.update()"
                        class="space-y-4"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="text-sm text-muted-foreground">
                                Your email address is unverified.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Click here to resend the verification email.
                                </Link>
                            </p>

                            <div
                                v-if="status === 'verification-link-sent'"
                                class="mt-2 text-sm font-medium text-green-600"
                            >
                                A new verification link has been sent to your email address.
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <Button :disabled="processing" data-test="update-profile-button">
                                Save changes
                            </Button>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="recentlySuccessful" class="text-sm text-green-600">
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <!-- Danger Zone -->
            <DeleteUser />
        </SettingsLayout>
    </TenantLayout>
</template>
