import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/asset_analysis.css',
                'resources/css/asset_dashboard.css',
                'resources/css/asset_directory.css',
                'resources/css/damage_queue.css',
                'resources/css/location_analysis.css',
                'resources/css/log_history.css',
                'resources/css/register.css',
                'resources/css/staff_dashboard.css',
                'resources/css/style.css',
                'resources/css/tag_generator.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
