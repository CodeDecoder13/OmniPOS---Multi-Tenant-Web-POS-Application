import { usePage } from '@inertiajs/vue3';
import type { ReceiptData } from '@/components/ReceiptTemplate.vue';

export interface KotData {
    orderNumber: string;
    dateTime: string;
    tableName?: string | null;
    orderType?: string;
    items: {
        name: string;
        quantity: number;
        variations?: { group_name: string; option_name: string }[];
        addons?: { name: string }[];
    }[];
    notes?: string | null;
    kitchenNotes?: string | null;
}

export interface BarcodeData {
    productName: string;
    sku: string;
    price: string;
    svgHtml: string;
}

const CURRENCY_MAP: Record<string, string> = {
    PHP: 'en-PH',
    USD: 'en-US',
    EUR: 'de-DE',
    GBP: 'en-GB',
    JPY: 'ja-JP',
    KRW: 'ko-KR',
    CNY: 'zh-CN',
    SGD: 'en-SG',
    AUD: 'en-AU',
    CAD: 'en-CA',
    INR: 'en-IN',
    THB: 'th-TH',
    MYR: 'ms-MY',
    IDR: 'id-ID',
    VND: 'vi-VN',
};

function getCurrencyInfo(): { code: string; locale: string } {
    try {
        const page = usePage();
        const tenant = page.props.tenant as any;
        const code = tenant?.settings?.currency || 'PHP';
        const locale = CURRENCY_MAP[code] || 'en-US';
        return { code, locale };
    } catch {
        return { code: 'PHP', locale: 'en-PH' };
    }
}

function formatCurrency(amount: number | string, currencyCode?: string): string {
    const { code, locale } = currencyCode
        ? { code: currencyCode, locale: CURRENCY_MAP[currencyCode] || 'en-US' }
        : getCurrencyInfo();

    return new Intl.NumberFormat(locale, { style: 'currency', currency: code }).format(Number(amount));
}

function openPrintWindow(html: string): void {
    const win = window.open('', '_blank', 'width=400,height=600');
    if (!win) return;
    win.document.write(html);
    win.document.close();
    win.onafterprint = () => win.close();
    win.onload = () => win.print();
}

function buildReceiptHtml(data: ReceiptData, showPayment: boolean): string {
    const sep = '<div style="border-bottom:1px dashed #000;margin:4px 0"></div>';
    const row = (l: string, r: string) => `<div style="display:flex;justify-content:space-between;gap:4px"><span>${l}</span><span>${r}</span></div>`;

    let html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Receipt - ${data.orderNumber || 'Preview'}</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Courier New',Courier,monospace;font-size:11px;color:#000;width:302px;margin:0 auto;line-height:1.4;padding:4px}
.center{text-align:center}.bold{font-weight:bold}.small{font-size:10px;color:#333}.total{font-weight:bold;font-size:13px;border-top:1px dashed #000;padding-top:4px;margin-top:4px}
@media print{@page{size:80mm auto;margin:2mm}body{width:100%}}</style></head><body>`;

    // Store header
    html += `<div class="center"><div class="bold" style="font-size:13px">${data.storeName}</div>`;
    if (data.storeAddress) html += `<div class="small">${data.storeAddress}</div>`;
    if (data.storePhone) html += `<div class="small">${data.storePhone}</div>`;
    if (data.receiptHeader) html += `<div class="small" style="white-space:pre-line;margin-top:2px">${data.receiptHeader}</div>`;
    html += `</div>${sep}`;

    // Order info
    if (data.orderNumber) html += row('Order:', data.orderNumber);
    html += row('Date:', data.dateTime);
    html += row('Cashier:', data.cashier);
    if (data.customer) html += row('Customer:', data.customer);
    if (data.tableName) html += row('Table:', data.tableName);
    if (data.orderType) html += `<div class="bold center">** ${data.orderType} **</div>`;
    html += sep;

    // Items
    for (const item of data.items) {
        html += row(item.name, formatCurrency(item.subtotal));
        html += `<div class="small">&nbsp;${item.quantity} x ${formatCurrency(item.price)}</div>`;
    }
    html += sep;

    // Totals
    html += row('Subtotal', formatCurrency(data.subtotal));
    if (data.discount && data.discount > 0) html += row('Discount', `-${formatCurrency(data.discount)}`);
    if (data.promotionDiscount && data.promotionDiscount > 0) {
        html += row(`Promo${data.promotionCode ? ` (${data.promotionCode})` : ''}`, `-${formatCurrency(data.promotionDiscount)}`);
    }
    if (data.tax && data.tax > 0) html += row(data.taxLabel || 'Tax', formatCurrency(data.tax));
    html += `<div class="total">${row('TOTAL', formatCurrency(data.total))}</div>`;

    // Payment
    if (showPayment && data.paymentMethod) {
        html += sep;
        const methodLabel = data.paymentMethod === 'e_wallet' ? 'E-Wallet' : data.paymentMethod.charAt(0).toUpperCase() + data.paymentMethod.slice(1);
        html += row('Payment', methodLabel);
        if (data.amountTendered) html += row('Tendered', formatCurrency(data.amountTendered));
        if (data.change && data.change > 0) html += row('Change', formatCurrency(data.change));
        if (data.referenceNumber) html += row('Ref#', data.referenceNumber);
    }

    html += sep;

    // Footer
    html += `<div class="center small" style="color:#666;padding-top:4px;white-space:pre-line">${data.receiptFooter || 'Thank you for your purchase!'}</div>`;
    html += '</body></html>';

    return html;
}

function buildKotHtml(data: KotData): string {
    const sep = '<div style="border-bottom:1px dashed #000;margin:6px 0"></div>';

    let html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>KOT - ${data.orderNumber}</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Courier New',Courier,monospace;font-size:12px;color:#000;width:302px;margin:0 auto;line-height:1.4;padding:4px}
.center{text-align:center}.bold{font-weight:bold}.big{font-size:16px}.small{font-size:10px;color:#333}
@media print{@page{size:80mm auto;margin:2mm}body{width:100%}}</style></head><body>`;

    // KOT Header
    html += `<div class="center bold big" style="letter-spacing:2px;padding:4px 0">** KOT **</div>${sep}`;
    html += `<div class="center bold big" style="padding:2px 0">Order: ${data.orderNumber}</div>`;
    html += `<div class="center">${data.dateTime}</div>`;
    if (data.tableName) html += `<div class="center bold" style="font-size:14px">Table: ${data.tableName}</div>`;
    if (data.orderType) html += `<div class="center bold">** ${data.orderType} **</div>`;
    html += sep;

    // Items (no prices)
    for (const item of data.items) {
        html += `<div style="display:flex;gap:8px;padding:2px 0"><span class="bold" style="font-size:14px;min-width:24px">${item.quantity}x</span><span class="bold" style="font-size:13px">${item.name}</span></div>`;
        if (item.variations?.length) {
            for (const v of item.variations) {
                html += `<div class="small" style="padding-left:32px">- ${v.group_name}: ${v.option_name}</div>`;
            }
        }
        if (item.addons?.length) {
            for (const a of item.addons) {
                html += `<div class="small" style="padding-left:32px">+ ${a.name}</div>`;
            }
        }
    }

    if (data.notes) {
        html += sep;
        html += `<div class="bold">Notes:</div><div>${data.notes}</div>`;
    }
    if (data.kitchenNotes) {
        html += sep;
        html += `<div class="bold">Kitchen Notes:</div><div>${data.kitchenNotes}</div>`;
    }

    html += sep;
    html += `<div class="center small" style="color:#666">Printed: ${new Date().toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'medium' })}</div>`;
    html += '</body></html>';

    return html;
}

function buildBarcodeHtml(data: BarcodeData): string {
    return `<!DOCTYPE html><html><head><title>Barcode - ${data.productName}</title>
<style>body{margin:0;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;font-family:sans-serif}
.label{text-align:center;padding:10px}.product-name{font-size:14px;font-weight:bold;margin-bottom:4px}.price{font-size:12px;color:#555;margin-top:4px}
@media print{body{height:auto}}</style></head><body>
<div class="label"><div class="product-name">${data.productName}</div>${data.svgHtml}<div class="price">${Number(data.price).toFixed(2)}</div></div>
</body></html>`;
}

export function usePrinter() {
    function printReceipt(data: ReceiptData, showPayment = true) {
        openPrintWindow(buildReceiptHtml(data, showPayment));
    }

    function printKot(data: KotData) {
        openPrintWindow(buildKotHtml(data));
    }

    function printBarcode(data: BarcodeData) {
        openPrintWindow(buildBarcodeHtml(data));
    }

    return { printReceipt, printKot, printBarcode };
}
