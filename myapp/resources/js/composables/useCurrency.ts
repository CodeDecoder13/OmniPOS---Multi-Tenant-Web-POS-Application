import { usePage } from '@inertiajs/vue3';

interface CurrencyConfig {
    code: string;
    locale: string;
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

function getCurrencyConfig(): CurrencyConfig {
    const page = usePage();
    const tenant = page.props.tenant as any;
    const code = tenant?.settings?.currency || 'PHP';
    const locale = CURRENCY_MAP[code] || 'en-US';

    return { code, locale };
}

export function useCurrency() {
    const { code, locale } = getCurrencyConfig();

    function formatCurrency(amount: string | number): string {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: code,
        }).format(Number(amount));
    }

    function formatCurrencyShort(amount: string | number): string {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: code,
            notation: 'compact',
        }).format(Number(amount));
    }

    return { formatCurrency, formatCurrencyShort, currencyCode: code };
}
