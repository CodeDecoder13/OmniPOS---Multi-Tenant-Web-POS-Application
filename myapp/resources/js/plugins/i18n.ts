import { createI18n } from 'vue-i18n';
import en from '@/lang/en.json';
import ja from '@/lang/ja.json';
import fil from '@/lang/fil.json';

export const supportedLocales = [
    { code: 'en', name: 'English' },
    { code: 'ja', name: '日本語' },
    { code: 'fil', name: 'Filipino' },
] as const;

function getInitialLocale(): string {
    if (typeof localStorage !== 'undefined') {
        const stored = localStorage.getItem('locale');
        if (stored && ['en', 'ja', 'fil'].includes(stored)) {
            return stored;
        }
    }
    if (typeof document !== 'undefined') {
        try {
            const el = document.getElementById('app');
            const dataPage = el?.dataset.page;
            if (dataPage) {
                const page = JSON.parse(dataPage);
                const lang = page?.props?.tenant?.settings?.default_language;
                if (lang && ['en', 'ja', 'fil'].includes(lang)) {
                    return lang;
                }
            }
        } catch {
            // ignore
        }
    }
    return 'en';
}

const i18n = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: { en, ja, fil },
});

export default i18n;
