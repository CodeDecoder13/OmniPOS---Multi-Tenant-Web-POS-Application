<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { GraduationCap, Accessibility, UserRound, Tag, X, ChevronLeft, IdCard, Check } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useCurrency } from '@/composables/useCurrency';

interface PresetDiscount {
    id: number;
    code: string;
    name: string;
    type: string;
    value: string;
}

interface CustomerVerification {
    name: string;
    id_number: string;
    birthday: string;
}

const props = defineProps<{
    open: boolean;
    presetDiscounts: PresetDiscount[];
    subtotal: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'apply': [discount: PresetDiscount, customer: CustomerVerification];
}>();

const { formatCurrency } = useCurrency();

// State
const step = ref<'select' | 'verify'>('select');
const selectedDiscount = ref<PresetDiscount | null>(null);
const customerName = ref('');
const customerId = ref('');
const customerBirthday = ref('');
const errors = ref<Record<string, string>>({});

const discountIcons: Record<string, any> = {
    student: GraduationCap,
    pwd: Accessibility,
    senior_citizen: UserRound,
};

const discountColors: Record<string, { bg: string; border: string; icon: string; badge: string }> = {
    student: {
        bg: 'bg-blue-50 dark:bg-blue-950/40',
        border: 'border-blue-200 dark:border-blue-800',
        icon: 'text-blue-600 dark:text-blue-400',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    },
    pwd: {
        bg: 'bg-purple-50 dark:bg-purple-950/40',
        border: 'border-purple-200 dark:border-purple-800',
        icon: 'text-purple-600 dark:text-purple-400',
        badge: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    },
    senior_citizen: {
        bg: 'bg-amber-50 dark:bg-amber-950/40',
        border: 'border-amber-200 dark:border-amber-800',
        icon: 'text-amber-600 dark:text-amber-400',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
    },
};

const discountLabels: Record<string, string> = {
    student: 'Student',
    pwd: 'PWD',
    senior_citizen: 'Senior Citizen',
};

const discountAmount = computed(() => {
    if (!selectedDiscount.value) return 0;
    return Math.round(props.subtotal * (parseFloat(selectedDiscount.value.value) / 100) * 100) / 100;
});

const computedAge = computed(() => {
    if (!customerBirthday.value) return null;
    const today = new Date();
    const birth = new Date(customerBirthday.value);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
});

function selectDiscount(discount: PresetDiscount) {
    selectedDiscount.value = discount;
    step.value = 'verify';
    errors.value = {};
}

function goBack() {
    step.value = 'select';
    selectedDiscount.value = null;
    resetForm();
}

function resetForm() {
    customerName.value = '';
    customerId.value = '';
    customerBirthday.value = '';
    errors.value = {};
}

function validate(): boolean {
    errors.value = {};

    if (!customerName.value.trim()) {
        errors.value.name = 'Full name is required';
    }
    if (!customerId.value.trim()) {
        errors.value.id_number = 'ID number is required';
    }
    if (!customerBirthday.value) {
        errors.value.birthday = 'Birthday is required';
    } else {
        const birth = new Date(customerBirthday.value);
        if (birth > new Date()) {
            errors.value.birthday = 'Birthday cannot be in the future';
        }
        // Validate senior citizen age
        if (selectedDiscount.value?.type === 'senior_citizen' && computedAge.value !== null && computedAge.value < 60) {
            errors.value.birthday = 'Must be 60 years or older for Senior Citizen discount';
        }
    }

    return Object.keys(errors.value).length === 0;
}

function confirmDiscount() {
    if (!validate() || !selectedDiscount.value) return;

    emit('apply', selectedDiscount.value, {
        name: customerName.value.trim(),
        id_number: customerId.value.trim(),
        birthday: customerBirthday.value,
    });

    closePanel();
}

function closePanel() {
    emit('update:open', false);
    step.value = 'select';
    selectedDiscount.value = null;
    resetForm();
}

// Reset when panel closes
watch(() => props.open, (open) => {
    if (!open) {
        step.value = 'select';
        selectedDiscount.value = null;
        resetForm();
    }
});
</script>

<template>
    <Sheet :open="open" @update:open="closePanel">
        <SheetContent side="right" class="w-full sm:max-w-md p-0 flex flex-col">
            <SheetHeader class="px-5 pt-5 pb-3 border-b shrink-0">
                <div class="flex items-center gap-3">
                    <Button
                        v-if="step === 'verify'"
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 shrink-0"
                        @click="goBack"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <div class="flex items-center gap-2 min-w-0">
                        <Tag class="h-5 w-5 text-muted-foreground shrink-0" />
                        <SheetTitle class="text-base">
                            {{ step === 'select' ? 'Apply Discount' : (discountLabels[selectedDiscount?.type ?? ''] ?? '') + ' Discount' }}
                        </SheetTitle>
                    </div>
                </div>
            </SheetHeader>

            <!-- Step 1: Select Discount Type -->
            <div v-if="step === 'select'" class="flex-1 overflow-y-auto p-5">
                <p class="text-sm text-muted-foreground mb-4">Select a discount type to apply:</p>
                <div class="space-y-3">
                    <button
                        v-for="discount in presetDiscounts"
                        :key="discount.id"
                        class="w-full text-left rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-md active:scale-[0.98] cursor-pointer"
                        :class="[
                            discountColors[discount.type]?.bg ?? 'bg-muted',
                            discountColors[discount.type]?.border ?? 'border-border',
                        ]"
                        @click="selectDiscount(discount)"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-white dark:bg-gray-900 shadow-sm"
                            >
                                <component
                                    :is="discountIcons[discount.type] ?? Tag"
                                    class="h-6 w-6"
                                    :class="discountColors[discount.type]?.icon ?? 'text-muted-foreground'"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm">
                                    {{ discountLabels[discount.type] ?? discount.name }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Requires valid {{ discountLabels[discount.type] ?? '' }} ID
                                </p>
                            </div>
                            <span
                                class="text-sm font-bold px-3 py-1.5 rounded-full shrink-0"
                                :class="discountColors[discount.type]?.badge ?? 'bg-muted'"
                            >
                                {{ discount.value }}% OFF
                            </span>
                        </div>
                    </button>
                </div>

                <div v-if="presetDiscounts.length === 0" class="text-center py-12 text-muted-foreground">
                    <Tag class="h-10 w-10 mx-auto mb-3 opacity-30" />
                    <p class="text-sm">No preset discounts available</p>
                </div>
            </div>

            <!-- Step 2: ID Verification Form -->
            <div v-else-if="step === 'verify' && selectedDiscount" class="flex-1 overflow-y-auto p-5">
                <!-- Selected discount summary -->
                <div
                    class="rounded-xl border-2 p-4 mb-5"
                    :class="[
                        discountColors[selectedDiscount.type]?.bg ?? 'bg-muted',
                        discountColors[selectedDiscount.type]?.border ?? 'border-border',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <component
                                :is="discountIcons[selectedDiscount.type] ?? Tag"
                                class="h-5 w-5"
                                :class="discountColors[selectedDiscount.type]?.icon ?? 'text-muted-foreground'"
                            />
                            <span class="font-semibold text-sm">
                                {{ discountLabels[selectedDiscount.type] ?? selectedDiscount.name }}
                            </span>
                        </div>
                        <span class="text-sm font-bold" :class="discountColors[selectedDiscount.type]?.icon">
                            -{{ formatCurrency(discountAmount) }}
                        </span>
                    </div>
                </div>

                <!-- Verification form -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 mb-1">
                        <IdCard class="h-4 w-4 text-muted-foreground" />
                        <p class="text-sm font-medium">ID Verification</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="dc-name" class="text-xs font-medium">Full Name (as shown on ID)</Label>
                        <Input
                            id="dc-name"
                            v-model="customerName"
                            placeholder="Juan Dela Cruz"
                            class="h-11 text-sm"
                            autocomplete="off"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="dc-id" class="text-xs font-medium">ID Number</Label>
                        <Input
                            id="dc-id"
                            v-model="customerId"
                            placeholder="Enter ID number"
                            class="h-11 text-sm"
                            autocomplete="off"
                        />
                        <p v-if="errors.id_number" class="text-xs text-destructive">{{ errors.id_number }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="dc-bday" class="text-xs font-medium">Birthday</Label>
                        <Input
                            id="dc-bday"
                            v-model="customerBirthday"
                            type="date"
                            class="h-11 text-sm"
                            :max="new Date().toISOString().split('T')[0]"
                        />
                        <p v-if="errors.birthday" class="text-xs text-destructive">{{ errors.birthday }}</p>
                        <p v-if="computedAge !== null && !errors.birthday" class="text-xs text-muted-foreground">
                            Age: {{ computedAge }} years old
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="step === 'verify'" class="border-t p-5 shrink-0">
                <Button
                    class="w-full h-12 text-sm font-semibold"
                    @click="confirmDiscount"
                >
                    <Check class="h-4 w-4 mr-2" />
                    Apply {{ selectedDiscount?.value }}% Discount &mdash; {{ formatCurrency(discountAmount) }} off
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
