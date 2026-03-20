<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import JsBarcode from 'jsbarcode';
import { Button } from '@/components/ui/button';
import { Printer } from 'lucide-vue-next';
import { usePrinter } from '@/composables/usePrinter';

const props = defineProps<{
    sku: string;
    productName: string;
    price: string;
}>();

const svgRef = ref<SVGSVGElement | null>(null);
const { printBarcode } = usePrinter();

function renderBarcode() {
    if (svgRef.value && props.sku) {
        JsBarcode(svgRef.value, props.sku, {
            format: 'CODE128',
            width: 2,
            height: 50,
            displayValue: true,
            fontSize: 14,
            margin: 5,
        });
    }
}

onMounted(renderBarcode);
watch(() => props.sku, renderBarcode);

function printLabel() {
    printBarcode({
        productName: props.productName,
        sku: props.sku,
        price: props.price,
        svgHtml: svgRef.value?.outerHTML ?? '',
    });
}
</script>

<template>
    <div class="inline-flex flex-col items-center gap-2">
        <div class="rounded border bg-white p-3 text-center">
            <p class="text-xs font-semibold">{{ productName }}</p>
            <svg ref="svgRef"></svg>
            <p class="text-xs text-muted-foreground">{{ Number(price).toFixed(2) }}</p>
        </div>
        <Button variant="outline" size="sm" @click="printLabel">
            <Printer class="mr-1 h-4 w-4" />
            Print Label
        </Button>
    </div>
</template>
