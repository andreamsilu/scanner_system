import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/legacy.css',
                'resources/js/app.js',
                'resources/js/scanner.js',
            ],
            refresh: true,
        }),
        // Keep classic script order for older inline jQuery screens.
        viteStaticCopy({
            targets: [
                { src: 'node_modules/jquery/dist/jquery.min.js', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/bootstrap-icons/font/bootstrap-icons.css', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2', dest: 'legacy/fonts', rename: { stripBase: true } },
                { src: 'node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff', dest: 'legacy/fonts', rename: { stripBase: true } },
                { src: 'node_modules/datatables.net/js/jquery.dataTables.min.js', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/toastr/build/toastr.min.js', dest: 'legacy', rename: { stripBase: true } },
                { src: 'node_modules/toastr/build/toastr.min.css', dest: 'legacy', rename: { stripBase: true } },
            ],
        }),
    ],
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
