import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        // ✅ Разрешить подключения из локальной сети
        host: '0.0.0.0', 
        port: 5173,
        hmr: {
            // ✅ Укажите ваш реальный IP для Hot Module Replacement
            host: '192.168.0.45', 
        },
    },
});
