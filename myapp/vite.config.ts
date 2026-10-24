import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig(({ isSsrBuild }) => ({
    resolve: {
        // vue-i18n 12's default client bundle ships without a working message
        // compiler, so production builds render raw keys like "settings.title".
        // The full browser build compiles our JSON messages at runtime.
        // SSR keeps the node build.
        alias: isSsrBuild ? {} : { 'vue-i18n': 'vue-i18n/dist/vue-i18n.esm-browser.prod.js' },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
}));
