<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import TenantLayout from '@/layouts/TenantLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';
import type { BreadcrumbItem } from '@/types';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    hasPassword?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
    hasPassword: true,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: edit(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <Head title="Security settings" />

        <h1 class="sr-only">Security settings</h1>

        <SettingsLayout>
            <!-- Password Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <KeyRound class="h-4.5 w-4.5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-base">
                                {{ hasPassword ? 'Update password' : 'Set a password' }}
                            </CardTitle>
                            <CardDescription>
                                {{ hasPassword ? 'Ensure your account is using a long, random password to stay secure' : 'Set a password so you can also log in with your email and password' }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="SecurityController.update()"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="space-y-4"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div v-if="hasPassword" class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="New password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <Button :disabled="processing" data-test="update-password-button">
                                Save password
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

            <!-- Two-Factor Authentication Card -->
            <Card v-if="canManageTwoFactor">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="twoFactorEnabled ? 'bg-green-500/10' : 'bg-muted'">
                            <ShieldCheck class="h-4.5 w-4.5" :class="twoFactorEnabled ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <CardTitle class="text-base">Two-factor authentication</CardTitle>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        twoFactorEnabled
                                            ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                            : 'bg-muted text-muted-foreground',
                                    ]"
                                >
                                    {{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <CardDescription>Add an extra layer of security to your account</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="!twoFactorEnabled" class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.
                        </p>
                        <div>
                            <Button v-if="hasSetupData" @click="showSetupModal = true">
                                <ShieldCheck class="mr-2 h-4 w-4" />
                                Continue setup
                            </Button>
                            <Form
                                v-else
                                v-bind="enable()"
                                @success="showSetupModal = true"
                                #default="{ processing }"
                            >
                                <Button type="submit" :disabled="processing">
                                    Enable 2FA
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.
                        </p>
                        <Form v-bind="disable()" #default="{ processing }">
                            <Button variant="destructive" type="submit" :disabled="processing">
                                Disable 2FA
                            </Button>
                        </Form>
                        <TwoFactorRecoveryCodes />
                    </div>

                    <TwoFactorSetupModal
                        v-model:isOpen="showSetupModal"
                        :requiresConfirmation="requiresConfirmation"
                        :twoFactorEnabled="twoFactorEnabled"
                    />
                </CardContent>
            </Card>
        </SettingsLayout>
    </TenantLayout>
</template>
