import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import autoprefixer from 'autoprefixer';
import postcssNesting from 'postcss-nesting';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/blog.js',
                'resources/js/analytics-charts.js',
                'resources/css/filament/dashboard/theme.css',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
            prefetch: [
                'resources/js/components.js',
                'resources/js/admin.js',
            ],
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs', 'chart.js', 'clipboard', 'highlight.js'],
                    components: ['resources/js/components.js'],
                },
            },
        },
        sourcemap: process.env.NODE_ENV === 'development',
        assetsInlineLimit: 4096,
        cssCodeSplit: true,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
                drop_debugger: process.env.NODE_ENV === 'production',
            },
        },
    },
    optimizeDeps: {
        include: [
            'alpinejs',
            '@alpinejs/intersect',
            'chart.js',
            'clipboard',
            'highlight.js',
        ],
    },
    css: {
        devSourcemap: process.env.NODE_ENV === 'development',
        postcss: {
            plugins: [
                autoprefixer,
                postcssNesting,
            ],
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
        host: true,
        cors: {
            origin: [
                'http://localhost',
                'http://127.0.0.1',
                'http://localhost:8000',
                'http://127.0.0.1:8000',
                /^https?:\/\/.*\.test(:\d+)?$/,
                /^https?:\/\/.*\.localhost(:\d+)?$/,
            ],
        },
    },
    experimental: {
        renderBuiltUrl(filename, { hostType }) {
            if (hostType === 'js') {
                return { js: `/${filename}` };
            } else {
                return { css: `/${filename}` };
            }
        },
    },
});
