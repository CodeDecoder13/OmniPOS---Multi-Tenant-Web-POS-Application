<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch, type Component } from 'vue';
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
    ShoppingCart, Coffee, Utensils, Wine, Store, Shirt, Cake, Pill, Wrench, LayoutGrid,
};

const props = defineProps<{
    plans: Plan[];
    businessTypes: BusinessTypeOption[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    store_name: '',
    business_type: '',
    plan: 'free',
    promo_code: '',
});

// Beta: locked to free plan only
const isBeta = true;

onMounted(() => {
    if (!isBeta) {
        const urlParams = new URLSearchParams(window.location.search);
        const planParam = urlParams.get('plan');
        if (planParam && props.plans.some(p => p.slug === planParam)) {
            form.plan = planParam;
        }
    }
});

// --- Step wizard logic ---
const currentStep = ref(1);
const totalSteps = 4;
const direction = ref<'forward' | 'backward'>('forward');

const steps = [
    { number: 1, title: 'Your Account', icon: User },
    { number: 2, title: 'Your Business', icon: Store },
    { number: 3, title: 'Choose a Plan', icon: CreditCard },
    { number: 4, title: 'Secure Account', icon: Lock },
];

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
    if (currentStep.value < totalSteps) {
        direction.value = 'forward';
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
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

function selectPlan(slug: string) {
    if (isBeta && slug !== 'free') return;
    form.plan = slug;
}

function selectBusinessType(value: string) {
    form.business_type = value;
}

function submit() {
    if (!validateStep(currentStep.value)) return;
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            // Navigate to the first step that has an error
            const errorFields = Object.keys(errors);
            if (errorFields.length > 0) {
                const firstField = errorFields[0];
                const targetStep = fieldStepMap[firstField] ?? 1;
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
                                <span v-else>{{ step.number }}</span>
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
                                    />
                                    <InputError :message="stepErrors.email || form.errors.email" />
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
                                        isBeta && Number(plan.price) > 0
                                            ? 'cursor-not-allowed opacity-50 border-gray-200 dark:border-gray-700'
                                            : 'cursor-pointer',
                                        !(isBeta && Number(plan.price) > 0) && form.plan === plan.slug
                                            ? 'border-teal-600 bg-teal-50 dark:border-teal-500 dark:bg-teal-950/20'
                                            : !(isBeta && Number(plan.price) > 0)
                                                ? 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                                                : ''
                                    ]"
                                    @click="selectPlan(plan.slug)"
                                >
                                    <!-- Coming Soon badge for paid plans during beta -->
                                    <div v-if="isBeta && Number(plan.price) > 0" class="absolute -top-2.5 right-3 rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-semibold text-white">
                                        Coming Soon
                                    </div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="font-semibold">{{ plan.name }}</span>
                                            <span v-if="plan.slug === 'pro' && !isBeta" class="ml-2 rounded-full bg-teal-600 px-2 py-0.5 text-xs text-white">Popular</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-2xl font-bold" :class="isBeta && Number(plan.price) > 0 ? 'text-gray-400 dark:text-gray-500' : 'text-teal-600'">
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

                            <!-- Beta notice -->
                            <p v-if="isBeta" class="mt-3 text-center text-xs text-muted-foreground">
                                Currently in beta — all accounts start on the Free plan. Paid plans coming soon!
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

                        <!-- Step 4: Secure Account -->
                        <div v-else-if="currentStep === 4" key="step4">
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
                            v-else-if="currentStep > 1"
                            type="button"
                            variant="outline"
                            @click="prevStep"
                        >
                            <ArrowLeft class="size-4 mr-1" />
                            Back
                        </Button>

                        <Button
                            v-if="currentStep < totalSteps"
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
