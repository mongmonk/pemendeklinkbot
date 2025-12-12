import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Deteksi environment
const isProduction = process.env.NODE_ENV === 'production';
const isLocal = process.env.NODE_ENV === 'local' || !isProduction;

// Tentukan URL berdasarkan environment
const baseUrl = isProduction
    ? 'https://aqwam.id'
    : 'http://aqwam.test';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build',
            publicDirectory: 'public',
        }),
    ],
    
    // Konfigurasi server development
    server: {
        host: 'aqwam.test',
        port: 5173,
        strictPort: true,
    },
    
    // Konfigurasi build
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'assets/css/[name].[hash][extname]';
                    }
                    if (assetInfo.name && assetInfo.name.endsWith('.js')) {
                        return 'assets/js/[name].[hash][extname]';
                    }
                    return 'assets/[name].[hash][extname]';
                },
                chunkFileNames: 'assets/js/[name].[hash].js',
                entryFileNames: 'assets/js/[name].[hash].js',
            },
        },
    },
    
    // Konfigurasi base URL untuk asset
    base: isProduction ? '/' : '/',
    
    // Konfigurasi environment variables
    define: {
        __APP_ENV__: JSON.stringify(isProduction ? 'production' : 'local'),
        __BASE_URL__: JSON.stringify(baseUrl),
    },
});
