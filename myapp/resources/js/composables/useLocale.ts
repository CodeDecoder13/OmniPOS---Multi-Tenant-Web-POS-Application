import { useI18n } from 'vue-i18n';

const LOCALE_KEY = 'locale';

export function useLocale() {
    const { locale } = useI18n();

    function initLocale() {
        // i18n plugin already resolved the correct locale at boot from
        // localStorage -> tenant default -> 'en' (see plugins/i18n.ts).
        // Just sync the document lang attribute.
        document.documentElement.lang = locale.value;
    }

    function setLocale(code: string) {
        locale.value = code;
        localStorage.setItem(LOCALE_KEY, code);
        document.documentElement.lang = code;
    }

    return { initLocale, setLocale, currentLocale: locale };
}
