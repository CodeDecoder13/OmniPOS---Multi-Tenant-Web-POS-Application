<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
    InputOTPSeparator,
} from '@/components/ui/input-otp';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send, verifyOtp } from '@/routes/verification';

defineProps<{
    status?: string;
}>();

const code = ref('');

const form = useForm({
    code: '',
});

const submitOtp = () => {
    form.code = code.value;
    form.post(verifyOtp.url(), {
        preserveScroll: true,
        onError: () => {
            code.value = '';
        },
    });
};
</script>

<template>
    <AuthLayout
        title="Verify your email"
        description="Enter the 6-digit code we sent to your email address."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            A new verification code has been sent to your email address.
        </div>

        <div class="space-y-6">
            <!-- OTP Input Form -->
            <form @submit.prevent="submitOtp" class="space-y-4">
                <div class="flex flex-col items-center justify-center space-y-3 text-center">
                    <div class="flex w-full items-center justify-center">
                        <InputOTP
                            id="otp"
                            v-model="code"
                            :maxlength="6"
                            :disabled="form.processing"
                            autofocus
                        >
                            <InputOTPGroup>
                                <InputOTPSlot :index="0" />
                                <InputOTPSlot :index="1" />
                                <InputOTPSlot :index="2" />
                            </InputOTPGroup>

                            <InputOTPSeparator />

                            <InputOTPGroup>
                                <InputOTPSlot :index="3" />
                                <InputOTPSlot :index="4" />
                                <InputOTPSlot :index="5" />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="form.errors.code" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing || code.length < 6">
                    <Spinner v-if="form.processing" />
                    Verify Email
                </Button>
            </form>

            <!-- Resend Code -->
            <div class="text-center">
                <Form
                    v-bind="send()"
                    class="inline"
                    v-slot="{ processing }"
                >
                    <p class="text-sm text-muted-foreground mb-2">
                        Didn't receive the code?
                    </p>
                    <Button :disabled="processing" variant="link" size="sm" class="text-teal-600 hover:text-teal-700">
                        <Spinner v-if="processing" />
                        Resend code
                    </Button>
                </Form>
            </div>

            <!-- Logout -->
            <div class="text-center">
                <TextLink
                    :href="logout()"
                    as="button"
                    class="text-sm"
                >
                    Log out
                </TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
