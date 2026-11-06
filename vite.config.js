import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';


export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@features': path.resolve(__dirname, './resources/js/features'),
            '@ui': path.resolve(__dirname, './resources/js/ui'),
            '@pages': path.resolve(__dirname, './resources/js/pages'),
            '@shared': path.resolve(__dirname, './resources/js/shared'),
            '@layouts': path.resolve(__dirname, './resources/js/layouts'),
        },
    },
    server: {
        origin: 'http://portal_dev.local:5173',
        cors: true
    }
});
