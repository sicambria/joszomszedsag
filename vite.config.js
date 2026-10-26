import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/front.js',
                'resources/js/plugins/dashboard/script.js',
                'resources/sass/app.scss',
                'resources/sass/materialize/materialize.scss',
                'resources/sass/dashboard/style.scss',
            ],
            refresh: true,
        }),
    ],
});
