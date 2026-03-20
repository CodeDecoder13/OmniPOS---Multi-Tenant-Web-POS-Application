<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale } from '@/composables/useLocale';
import { supportedLocales } from '@/plugins/i18n';

const { setLocale, currentLocale } = useLocale();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-8 w-8">
                <Globe class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="loc in supportedLocales"
                :key="loc.code"
                @click="setLocale(loc.code)"
                :class="{ 'bg-accent': currentLocale === loc.code }"
            >
                {{ loc.name }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
