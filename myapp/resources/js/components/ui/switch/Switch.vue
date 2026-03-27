<script setup lang="ts">
import type { SwitchRootProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { SwitchRoot, SwitchThumb } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class']; checked?: boolean }>()
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'update:checked': [value: boolean]
}>()

const delegatedProps = reactiveOmit(props, 'class', 'checked', 'modelValue')
const effectiveValue = computed(() => props.modelValue ?? props.checked)

function onUpdate(value: boolean) {
    emit('update:modelValue', value)
    emit('update:checked', value)
}
</script>

<template>
    <SwitchRoot
        v-bind="delegatedProps"
        :model-value="effectiveValue"
        @update:model-value="onUpdate"
        data-slot="switch"
        :class="
            cn(
                'peer data-[state=checked]:bg-primary data-[state=unchecked]:bg-input focus-visible:border-ring focus-visible:ring-ring/50 inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    >
        <SwitchThumb
            data-slot="switch-thumb"
            :class="
                cn(
                    'bg-background pointer-events-none block size-4 rounded-full ring-0 shadow-lg transition-transform data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0',
                )
            "
        />
    </SwitchRoot>
</template>
