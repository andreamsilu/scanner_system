import prefixSelector from 'postcss-prefix-selector';
import autoprefixer from 'autoprefixer';

export default {
    plugins: [
        {
            postcssPlugin: 'remove-nested-charset',
            AtRule: { charset: (rule) => { if (rule.parent.type !== 'root') rule.remove(); } },
        },
        prefixSelector({
            prefix: '.legacy-ui',
            includeFiles: ['resources/css/legacy.css', 'resources\\css\\legacy.css'],
            // Bootstrap adds these elements to body, outside the legacy page.
            exclude: [/^\.modal-backdrop/, /^\.modal-open/],
        }),
        autoprefixer(),
    ],
};
