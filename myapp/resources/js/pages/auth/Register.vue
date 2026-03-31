<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch, type Component } from 'vue';
import GoogleSignInButton from '@/components/GoogleSignInButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Plan, BusinessTypeOption } from '@/types';
import {
    ShoppingCart,
    Coffee,
    Utensils,
    Wine,
    Store,
    Shirt,
    Cake,
    Pill,
    Wrench,
    Factory,
    LayoutGrid,
    User,
    CreditCard,
    Lock,
    Check,
    ArrowLeft,
    ArrowRight,
    Loader2,
} from 'lucide-vue-next';

const iconMap: Record<string, Component> = {
    ShoppingCart, Coffee, Utensils, Wine, Store, Shirt, Cake, Pill, Wrench, Factory, LayoutGrid,
};

interface GoogleUser {
    id: string;
    name: string;
    email: string;
}

const props = defineProps<{
    plans: Plan[];
    businessTypes: BusinessTypeOption[];
    googleUser?: GoogleUser | null;
}>();

const isGoogleRegistration = computed(() => !!props.googleUser);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    store_name: '',
    business_type: '',
    plan: '',
    promo_code: '',
});

onMounted(() => {
    if (props.googleUser) {
        form.name = props.googleUser.name;
        form.email = props.googleUser.email;
        currentStep.value = 2;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const planParam = urlParams.get('plan');
    if (planParam && props.plans.some(p => p.slug === planParam && Number(p.price) > 0)) {
        form.plan = planParam;
    }
});

// --- Step wizard logic ---
const currentStep = ref(1);
const direction = ref<'forward' | 'backward'>('forward');

const allSteps = [
    { number: 1, title: 'Your Account', icon: User },
    { number: 2, title: 'Your Business', icon: Store },
    { number: 3, title: 'Choose a Plan', icon: CreditCard },
    { number: 4, title: 'Secure Account', icon: Lock },
];

const steps = computed(() =>
    isGoogleRegistration.value
        ? allSteps.filter(s => s.number !== 4)
        : allSteps,
);

const totalSteps = computed(() => steps.value.length);

const stepErrors = reactive<Record<string, string>>({});

function clearStepErrors() {
    Object.keys(stepErrors).forEach(k => delete stepErrors[k]);
}

function validateStep(step: number): boolean {
    clearStepErrors();
    if (step === 1) {
        if (!form.name.trim()) stepErrors.name = 'Name is required.';
        if (!form.email.trim()) {
            stepErrors.email = 'Email is required.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            stepErrors.email = 'Please enter a valid email address.';
        }
    } else if (step === 2) {
        if (!form.store_name.trim()) stepErrors.store_name = 'Store name is required.';
        if (!form.business_type) stepErrors.business_type = 'Please select a business type.';
    } else if (step === 3) {
        if (!form.plan) stepErrors.plan = 'Please select a plan.';
        else if (selectedPlanIsPaid.value && !promoState.valid) {
            stepErrors.promo_code = 'A valid promo code is required for this plan.';
        }
    } else if (step === 4) {
        if (!form.password) {
            stepErrors.password = 'Password is required.';
        } else if (form.password.length < 8) {
            stepErrors.password = 'Password must be at least 8 characters.';
        }
        if (!form.password_confirmation) {
            stepErrors.password_confirmation = 'Please confirm your password.';
        } else if (form.password !== form.password_confirmation) {
            stepErrors.password_confirmation = 'Passwords do not match.';
        }
    }
    return Object.keys(stepErrors).length === 0;
}

function nextStep() {
    if (!validateStep(currentStep.value)) return;
    direction.value = 'forward';
    // For Google registration, skip from step 3 directly (no step 4)
    if (isGoogleRegistration.value && currentStep.value === 3) return;
    if (currentStep.value < (isGoogleRegistration.value ? 3 : 4)) {
        currentStep.value++;
    }
}

function prevStep() {
    const minStep = isGoogleRegistration.value ? 2 : 1;
    if (currentStep.value > minStep) {
        clearStepErrors();
        direction.value = 'backward';
        currentStep.value--;
    }
}

const transitionName = computed(() =>
    direction.value === 'forward' ? 'slide-left' : 'slide-right'
);

const containerMaxWidth = computed(() =>
    currentStep.value === 2 || currentStep.value === 3 ? 'max-w-2xl' : 'max-w-lg'
);

// Promo code logic
const promoState = reactive({
    validating: false,
    valid: false,
    message: '',
    discount_type: '',
    discount_value: '',
});

const selectedPlanIsPaid = computed(() => {
    const plan = props.plans.find(p => p.slug === form.plan);
    return plan ? Number(plan.price) > 0 : false;
});

function resetPromoState() {
    promoState.validating = false;
    promoState.valid = false;
    promoState.message = '';
    promoState.discount_type = '';
    promoState.discount_value = '';
}

async function validatePromoCode() {
    if (!form.promo_code.trim()) return;
    promoState.validating = true;
    promoState.message = '';
    promoState.valid = false;

    try {
        const { data } = await axios.post('/promo-codes/validate', {
            code: form.promo_code,
            plan: form.plan,
        });
        promoState.valid = true;
        promoState.message = data.message;
        promoState.discount_type = data.discount_type;
        promoState.discount_value = data.discount_value;
    } catch {
        promoState.valid = false;
        promoState.message = 'Invalid or expired promo code.';
    } finally {
        promoState.validating = false;
    }
}

watch(() => form.plan, () => {
    form.promo_code = '';
    resetPromoState();
});

// Map server error fields to steps
const fieldStepMap: Record<string, number> = {
    name: 1,
    email: 1,
    store_name: 2,
    business_type: 2,
    plan: 3,
    promo_code: 3,
    password: 4,
    password_confirmation: 4,
};

const isFreePlan = (plan: Plan) => Number(plan.price) === 0;

function selectPlan(slug: string) {
    const plan = props.plans.find(p => p.slug === slug);
    if (plan && isFreePlan(plan)) return; // Free plan is locked
    form.plan = slug;
}

function selectBusinessType(value: string) {
    form.business_type = value;
}

function submit() {
    if (!validateStep(currentStep.value)) return;

    // For Google registration, clear password fields
    if (isGoogleRegistration.value) {
        form.password = '';
        form.password_confirmation = '';
    }

    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            // Navigate to the first step that has an error
            const errorFields = Object.keys(errors);
            if (errorFields.length > 0) {
                const firstField = errorFields[0];
                let targetStep = fieldStepMap[firstField] ?? 1;
                // Google users don't have step 4
                if (isGoogleRegistration.value && targetStep === 4) targetStep = 3;
                if (targetStep !== currentStep.value) {
                    direction.value = targetStep < currentStep.value ? 'backward' : 'forward';
                    currentStep.value = targetStep;
                }
            }
        },
    });
}
</script>

<template>
    <Head title="Register" />

    <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
        <div class="w-full transition-all duration-300" :class="containerMaxWidth">
            <!-- Logo -->
            <div class="mb-8 flex flex-col items-center gap-2">
                <Link href="/" class="flex items-center gap-2 font-medium">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-600">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold">Omni<span class="text-teal-600">POS</span></span>
                </Link>
            </div>

            <!-- Step Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <template v-for="(step, index) in steps" :key="step.number">
                        <!-- Connector line -->
                        <div
                            v-if="index > 0"
                            class="h-0.5 w-8 sm:w-16 transition-colors duration-300"
                            :class="currentStep >= step.number ? 'bg-teal-600' : 'bg-gray-300 dark:bg-gray-600'"
                        />
                        <!-- Step circle -->
                        <div class="flex flex-col items-center gap-1.5">
                            <div
                                class="flex size-9 items-center justify-center rounded-full text-sm font-semibold transition-all duration-300"
                                :class="
                                    currentStep > step.number
                                        ? 'bg-teal-600 text-white'
                                        : currentStep === step.number
                                          ? 'bg-teal-600 text-white ring-4 ring-teal-600/20'
                                          : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                "
                            >
                                <Check v-if="currentStep > step.number" class="size-4" />
                                <span v-else>{{ index + 1 }}</span>
                            </div>
                            <span
                                class="hidden sm:block text-xs font-medium transition-colors duration-300"
                                :class="
                                    currentStep >= step.number
                                        ? 'text-teal-600 dark:text-teal-400'
                                        : 'text-gray-400 dark:text-gray-500'
                                "
                            >
                                {{ step.title }}
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Card container -->
            <div class="rounded-xl border bg-card p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit">
                    <Transition :name="transitionName" mode="out-in">
                        <!-- Step 1: Your Account -->
                        <div v-if="currentStep === 1" key="step1">
                            <h2 class="mb-1 text-xl font-semibold">Your Account</h2>
                            <p class="mb-6 text-sm text-muted-foreground">Enter your personal details</p>

                            <!-- Google sign-in indicator -->
                            <div v-if="isGoogleRegistration" class="mb-5 flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-700 dark:border-teal-800 dark:bg-teal-950/20 dark:text-teal-400">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                Signed in with Google
                            </div>

                            <div class="grid gap-5">
                                <div class="grid gap-2">
                                    <Label for="name">Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        autofocus
                                        autocomplete="name"
                                        placeholder="Full name"
                                        :readonly="isGoogleRegistration"
                                        :class="{ 'bg-muted': isGoogleRegistration }"
                                    />
                                    <InputError :message="stepErrors.name || form.errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email">Email address</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="email@example.com"
                                        :readonly="isGoogleRegistration"
                                        :class="{ 'bg-muted': isGoogleRegistration }"
                                    />
                                    <InputError :message="stepErrors.email || form.errors.email" />
                                </div>
                            </div>

                            <!-- Or divider + Google sign-in -->
                            <div v-if="!isGoogleRegistration" class="mt-6">
                                <div class="relative my-2">
                                    <div class="absolute inset-0 flex items-center">
                                        <span class="w-full border-t" />
                                    </div>
                                    <div class="relative flex justify-center text-xs uppercase">
                                        <span class="bg-card px-2 text-muted-foreground">Or</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <GoogleSignInButton />
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Your Business -->
                        <div v-else-if="currentStep === 2" key="step2">
                            <h2 class="mb-1 text-xl font-semibold">Your Business</h2>
                            <p class="mb-6 text-sm text-muted-foreground">Tell us about your store</p>

                            <div class="grid gap-5">
                                <div class="grid gap-2">
                                    <Label for="store_name">Store Name</Label>
                                    <Input
                                        id="store_name"
                                        v-model="form.store_name"
                                        type="text"
                                        placeholder="My Awesome Store"
                                    />
                                    <InputError :message="stepErrors.store_name || form.errors.store_name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label>Business Type</Label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        <div
                                            v-for="bt in businessTypes"
                                            :key="bt.value"
                                            class="cursor-pointer rounded-lg border-2 p-3 transition"
                                            :class="form.business_type === bt.value
                                                ? 'border-teal-600 bg-teal-50 dark:border-teal-500 dark:bg-teal-950/20'
                                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'"
                                            @click="selectBusinessType(bt.value)"
                                        >
                                            <div class="flex items-center gap-2">
                                                <component
                                                    :is="iconMap[bt.icon]"
                                                    class="size-4 shrink-0"
                                                    :class="form.business_type === bt.value
                                                        ? 'text-teal-600 dark:text-teal-400'
                                                        : 'text-gray-400 dark:text-gray-500'"
                                                />
                                                <span class="text-sm font-medium">{{ bt.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <InputError :message="stepErrors.business_type || form.errors.business_type" />
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Choose a Plan -->
                        <div v-else-if="currentStep === 3" key="step3">
                            <h2 class="mb-1 text-xl font-semibold">Choose a Plan</h2>
                            <p class="mb-6 text-sm text-muted-foreground">Select the plan that fits your needs</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div
                                    v-for="plan in plans"
                                    :key="plan.slug"
                                    class="relative rounded-lg border-2 p-5 transition flex flex-col"
                                    :class="[
                                        isFreePlan(plan)
                                            ? 'cursor-not-allowed opacity-50 border-gray-200 dark:border-gray-700'
                                            : 'cursor-pointer',
                                        !isFreePlan(plan) && form.plan === plan.slug
                                            ? 'border-teal-600 bg-teal-50 dark:border-teal-500 dark:bg-teal-950/20'
                                            : !isFreePlan(plan)
                                                ? 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                                                : ''
                                    ]"
                                    @click="selectPlan(plan.slug)"
                                >
                                    <!-- Unavailable badge for free plan -->
                                    <div v-if="isFreePlan(plan)" class="absolute -top-2.5 right-3 rounded-full bg-gray-500 px-2.5 py-0.5 text-xs font-semibold text-white">
                                        Unavailable
                                    </div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="font-semibold">{{ plan.name }}</span>
                                            <span v-if="plan.slug === 'pro'" class="ml-2 rounded-full bg-teal-600 px-2 py-0.5 text-xs text-white">Popular</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-2xl font-bold" :class="isFreePlan(plan) ? 'text-gray-400 dark:text-gray-500' : 'text-teal-600'">
                                            {{ Number(plan.price) === 0 ? 'Free' : `₱${Number(plan.price).toLocaleString('en-PH')}` }}
                                        </span>
                                        <span v-if="Number(plan.price) > 0" class="text-sm text-gray-500">/mo</span>
                                    </div>
                                    <div class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ plan.max_branches ?? '∞' }} {{ plan.max_branches === 1 ? 'branch' : 'branches' }}</span>
                                        <span>{{ plan.max_users ?? '∞' }} users</span>
                                        <span>{{ plan.max_products ?? '∞' }} products</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notice -->
                            <p class="mt-3 text-center text-xs text-muted-foreground">
                                A valid promo code is required to activate your chosen plan.
                            </p>
                            <InputError :message="stepErrors.plan || form.errors.plan" class="mt-2" />

                            <!-- Promo Code (required for paid plans) -->
                            <div v-if="selectedPlanIsPaid" class="mt-4 space-y-2">
                                <Label for="promo_code">Promo Code <span class="text-red-500">*</span></Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="promo_code"
                                        v-model="form.promo_code"
                                        placeholder="Enter code"
                                        class="uppercase"
                                        :disabled="promoState.validating"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :disabled="!form.promo_code.trim() || promoState.validating"
                                        @click="validatePromoCode"
                                    >
                                        <Loader2 v-if="promoState.validating" class="mr-1 size-4 animate-spin" />
                                        Apply
                                    </Button>
                                </div>
                                <p
                                    v-if="promoState.message"
                                    class="text-sm"
                                    :class="promoState.valid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ promoState.message }}
                                </p>
                                <InputError :message="stepErrors.promo_code || form.errors.promo_code" />
                            </div>
                        </div>

                        <!-- Step 4: Secure Account (skipped for Google users) -->
                        <div v-else-if="currentStep === 4 && !isGoogleRegistration" key="step4">
                            <h2 class="mb-1 text-xl font-semibold">Secure Your Account</h2>
                            <p class="mb-6 text-sm text-muted-foreground">Create a strong password</p>

                            <div class="grid gap-5">
                                <div class="grid gap-2">
                                    <Label for="password">Password</Label>
                                    <PasswordInput
                                        id="password"
                                        v-model="form.password"
                                        autocomplete="new-password"
                                        placeholder="Password"
                                    />
                                    <InputError :message="stepErrors.password || form.errors.password" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="password_confirmation">Confirm password</Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        autocomplete="new-password"
                                        placeholder="Confirm password"
                                    />
                                    <InputError :message="stepErrors.password_confirmation || form.errors.password_confirmation" />
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Navigation buttons -->
                    <div class="mt-8 flex items-center justify-between gap-3">
                        <Button
                            v-if="currentStep === 1"
                            variant="outline"
                            as-child
                        >
                            <Link href="/">
                                <ArrowLeft class="size-4 mr-1" />
                                Go Back
                            </Link>
                        </Button>
                        <Button
                            v-else-if="isGoogleRegistration && currentStep === 2"
                            variant="outline"
                            as-child
                        >
                            <Link href="/register">
                                <ArrowLeft class="size-4 mr-1" />
                                Go Back
                            </Link>
                        </Button>
                        <Button
                            v-else-if="currentStep > 1"
                            type="button"
                            variant="outline"
                            @click="prevStep"
                        >
                            <ArrowLeft class="size-4 mr-1" />
                            Back
                        </Button>

                        <Button
                            v-if="isGoogleRegistration ? currentStep < 3 : currentStep < 4"
                            type="button"
                            class="bg-teal-600 hover:bg-teal-700"
                            @click="nextStep"
                        >
                            Next
                            <ArrowRight class="size-4 ml-1" />
                        </Button>

                        <Button
                            v-else
                            type="submit"
                            class="bg-teal-600 hover:bg-teal-700"
                            :disabled="form.processing"
                        >
                            <Spinner v-if="form.processing" class="mr-1" />
                            Create account
                        </Button>
                    </div>
                </form>

                <div class="mt-6 text-center text-sm text-muted-foreground">
                    Already have an account?
                    <Link href="/login" class="text-teal-600 underline underline-offset-4 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300">Log in</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Slide left (forward) */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.25s ease-in-out;
}
.slide-left-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* Slide right (backward) */
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.25s ease-in-out;
}
.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}
.slide-right-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>
